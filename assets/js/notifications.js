(function () {
  function qs(root, selector) {
    return root.querySelector(selector);
  }

  function postForm(url, data) {
    return fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: new URLSearchParams(data).toString(),
      credentials: 'same-origin'
    });
  }

  function esc(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function timeAgo(value) {
    var date = new Date(value);
    if (Number.isNaN(date.getTime())) {
      return '';
    }
    var seconds = Math.floor((Date.now() - date.getTime()) / 1000);
    if (seconds < 60) {
      return 'Just now';
    }
    if (seconds < 3600) {
      return Math.floor(seconds / 60) + 'm ago';
    }
    if (seconds < 86400) {
      return Math.floor(seconds / 3600) + 'h ago';
    }
    return Math.floor(seconds / 86400) + 'd ago';
  }

  function render(root, payload) {
    var badge = qs(root, '[data-notification-unread]');
    var list = qs(root, '[data-notification-list]');
    var itemClass = root.getAttribute('data-item-class') || 'site-nav__notification-item';
    var emptyClass = root.getAttribute('data-empty-class') || 'site-nav__notification-empty';
    if (!badge || !list) {
      return;
    }

    var unread = Number(payload.unread_count || 0);
    if (unread > 0) {
      badge.hidden = false;
      badge.textContent = unread > 99 ? '99+' : String(unread);
    } else {
      badge.hidden = true;
      badge.textContent = '0';
    }

    var items = Array.isArray(payload.notifications) ? payload.notifications : [];
    if (items.length === 0) {
      list.innerHTML = '<p class="' + emptyClass + '">No notifications yet.</p>';
      return;
    }

    var html = '';
    items.forEach(function (item) {
      var title = String(item.title || 'Notification');
      var message = String(item.message || '');
      var created = String(item.created_at || '');
      var unreadClass = Number(item.is_read) === 0 ? ' unread' : '';
      var href = item.action_url ? String(item.action_url) : '#';
      html +=
        '<a class="' + itemClass + unreadClass + '" href="' + esc(href) + '" data-notification-item data-id="' + esc(String(item.id || '')) + '">' +
          '<strong>' + esc(title) + '</strong>' +
          '<span>' + esc(message) + '</span>' +
          '<small>' + timeAgo(created) + '</small>' +
        '</a>';
    });

    list.innerHTML = html;
  }

  function init(root) {
    var base = root.getAttribute('data-notification-api-base') || '';
    var csrfToken = root.getAttribute('data-csrf-token') || '';
    var markAllBtn = qs(root, '[data-mark-all-read]');
    var list = qs(root, '[data-notification-list]');
    var emptyClass = root.getAttribute('data-empty-class') || 'site-nav__notification-empty';

    function load() {
      fetch(base + 'api/fetch_notifications.php', { credentials: 'same-origin' })
        .then(function (res) {
          if (!res.ok) {
            throw new Error('Fetch failed');
          }
          return res.json();
        })
        .then(function (data) {
          if (data && data.status === 'success') {
            render(root, data);
          }
        })
        .catch(function () {
          if (list) {
            list.innerHTML = '<p class="' + emptyClass + '">Unable to load notifications right now.</p>';
          }
          console.warn('Notification fetch failed for', base + 'api/fetch_notifications.php');
        });
    }

    root.addEventListener('click', function (event) {
      var target = event.target;
      if (!(target instanceof Element)) {
        return;
      }

      var item = target.closest('[data-notification-item]');
      if (item) {
        var id = item.getAttribute('data-id') || '';
        if (id) {
          postForm(base + 'api/mark_notification_read.php', {
            notification_id: id,
            csrf_token: csrfToken
          }).catch(function () {
            // Ignore UX errors for lightweight mark-read behavior.
          });
        }
      }
    });

    if (markAllBtn) {
      markAllBtn.addEventListener('click', function (event) {
        event.preventDefault();
        postForm(base + 'api/mark_all_notifications_read.php', {
          csrf_token: csrfToken
        })
          .then(function () {
            load();
          })
          .catch(function () {
            // Ignore UX errors for lightweight mark-all behavior.
          });
      });
    }

    load();
    window.setInterval(load, 45000);
  }

  var roots = document.querySelectorAll('[data-notification-root]');
  roots.forEach(init);
}());
