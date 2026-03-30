<?php

namespace Drupal\gen_zero_webform_rest\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * REST resource for submitting Drupal Webforms from a headless frontend.
 *
 * Accepts:
 *   POST /api/webform-submit?_format=json
 *   Body: { "webform_id": "contact", "data": { "name": "…", "email": "…" } }
 *
 * Returns:
 *   201 { "sid": 42 }  on success
 *   4xx on validation errors
 *
 * @RestResource(
 *   id = "gen_zero_webform_submit",
 *   label = @Translation("Gen Zero – Webform Submit"),
 *   uri_paths = {
 *     "create" = "/api/webform-submit"
 *   }
 * )
 */
class WebformSubmitResource extends ResourceBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('gen_zero_webform_rest'),
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param array $data
   *   Deserialized request body.
   *
   * @return \Drupal\rest\ResourceResponse
   *   201 with sid on success.
   */
  public function post(array $data): ResourceResponse {
    $webform_id = $data['webform_id'] ?? NULL;
    $fields     = $data['data'] ?? [];

    if (empty($webform_id) || !is_string($webform_id)) {
      throw new BadRequestHttpException('Missing or invalid webform_id.');
    }

    // Sanitise webform_id to prevent injection.
    $webform_id = preg_replace('/[^a-z0-9_]/', '', strtolower($webform_id));

    $webform = Webform::load($webform_id);
    if (!$webform) {
      throw new BadRequestHttpException("Webform '$webform_id' not found.");
    }

    if ($webform->isClosed()) {
      throw new UnprocessableEntityHttpException("Webform '$webform_id' is closed.");
    }

    if (!is_array($fields)) {
      throw new BadRequestHttpException('Request body must include a "data" object with field values.');
    }

    // Determine which webform elements expect arrays (checkboxes) or
    // associative arrays (likert), so that comma-separated strings and
    // JSON-encoded objects sent by the frontend are converted correctly.
    $elements = $webform->getElementsDecodedAndFlattened();
    $checkbox_types = [
      'checkboxes',
      'webform_checkboxes_other',
      'webform_entity_checkboxes',
      'webform_term_checkboxes',
    ];
    $likert_types = ['webform_likert'];

    // Sanitise field values.
    $clean_fields = [];
    foreach ($fields as $key => $value) {
      $safe_key = preg_replace('/[^a-z0-9_]/', '', strtolower((string) $key));
      if (empty($safe_key)) {
        continue;
      }

      // Look up the element type; fall back to treating as plain string.
      $element_type = $elements[$safe_key]['#type'] ?? NULL;

      if (in_array($element_type, $checkbox_types, TRUE)) {
        // Frontend sends "val1,val2" – Drupal webform expects a plain indexed
        // array of the selected option keys, e.g. ['newsletter', 'sms'].
        if (is_string($value) && $value !== '') {
          $clean_fields[$safe_key] = array_values(array_filter(array_map('trim', explode(',', $value))));
        }
        elseif (is_array($value)) {
          $clean_fields[$safe_key] = array_values(array_filter(array_map('strval', $value)));
        }
        else {
          $clean_fields[$safe_key] = [];
        }
      }
      elseif (in_array($element_type, $likert_types, TRUE)) {
        // Frontend sends a JSON-encoded object: '{"q1":"3","q2":"5"}'.
        if (is_string($value) && $value !== '') {
          $decoded = json_decode($value, TRUE);
          $clean_fields[$safe_key] = is_array($decoded) ? $decoded : [];
        }
        elseif (is_array($value)) {
          $clean_fields[$safe_key] = $value;
        }
        else {
          $clean_fields[$safe_key] = [];
        }
      }
      else {
        $clean_fields[$safe_key] = is_scalar($value) ? (string) $value : '';
      }
    }

    // Resolve submitter UID.
    // 0 = anonymous. A valid positive UID from the frontend session is used
    // when a user is logged in, so the submission is attributed correctly.
    $uid = 0;
    if (isset($data['uid']) && is_numeric($data['uid']) && (int) $data['uid'] > 0) {
      $candidate_uid = (int) $data['uid'];
      // Verify the user account exists before trusting the UID.
      if (User::load($candidate_uid)) {
        $uid = $candidate_uid;
      }
    }

    $submission = WebformSubmission::create([
      'webform_id' => $webform_id,
      'uid'        => $uid,
      'data'       => $clean_fields,
    ]);

    $errors = $submission->validate();
    if ($errors->count() > 0) {
      $messages = [];
      foreach ($errors as $violation) {
        $messages[] = $violation->getMessage();
      }
      throw new UnprocessableEntityHttpException(implode(' ', $messages));
    }

    $submission->save();

    $response = new ResourceResponse(['sid' => $submission->id()], 201);
    $response->addCacheableDependency(['#cache' => ['max-age' => 0]]);
    return $response;
  }

}
