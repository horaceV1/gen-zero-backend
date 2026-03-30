(function (Drupal) {
  'use strict';

  Drupal.behaviors.subscriptionTiersBlock = {
    attach: function (context) {
      var subscribeButtons = context.querySelectorAll('.subscription-tier__subscribe:not(.processed)');
      subscribeButtons.forEach(function (button) {
        button.classList.add('processed');
        button.addEventListener('click', function (e) {
          e.preventDefault();
          var tierId = this.getAttribute('data-tier-id');
          if (!tierId) {
            return;
          }
          this.classList.add('is-loading');
          this.textContent = Drupal.t('Subscribing...');

          fetch('/api/subscriptions/subscribe', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              tier_id: tierId,
            }),
          })
          .then(function (response) {
            if (response.ok) {
              window.location.reload();
            } else {
              return response.json().then(function (data) {
                throw new Error(data.message || 'Failed to subscribe');
              });
            }
          })
          .catch(function (err) {
            button.classList.remove('is-loading');
            button.textContent = Drupal.t('Subscribe');
            alert(err.message || Drupal.t('Could not subscribe. Please try again.'));
          });
        });
      });

      var cancelButtons = context.querySelectorAll('.subscription-tier__cancel:not(.processed)');
      cancelButtons.forEach(function (button) {
        button.classList.add('processed');
        button.addEventListener('click', function (e) {
          e.preventDefault();
          if (!confirm(Drupal.t('Are you sure you want to cancel this subscription?'))) {
            return;
          }
          var href = this.getAttribute('href');
          this.classList.add('is-loading');
          this.textContent = Drupal.t('Cancelling...');

          fetch(href, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
          })
          .then(function (response) {
            if (response.ok) {
              window.location.reload();
            } else {
              throw new Error('Failed to cancel');
            }
          })
          .catch(function () {
            button.classList.remove('is-loading');
            button.textContent = Drupal.t('Cancel Subscription');
            alert(Drupal.t('Could not cancel the subscription. Please try again.'));
          });
        });
      });
    },
  };
})(Drupal);
