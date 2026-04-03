// Voudou Ropes - Checklist Interactivity

// Toggle level accordion
function toggleLevel(levelNum) {
    const section = document.querySelector(`.level-section[data-level="${levelNum}"]`);
    if (section) {
        section.classList.toggle('collapsed');
    }
}

// Handle checkbox changes
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.check-seen, .check-done').forEach(function (cb) {
        cb.addEventListener('change', function () {
            const itemId = this.dataset.item;
            const field  = this.dataset.field;
            const value  = this.checked ? 1 : 0;

            fetch(BASE_URL + '/api/toggle.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ item_id: itemId, field: field, value: value })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    // Revert on failure
                    this.checked = !this.checked;
                    return;
                }

                // Update item visual
                const itemEl = this.closest('.checklist-item');
                const seen   = itemEl.querySelector('.check-seen').checked;
                const done   = itemEl.querySelector('.check-done').checked;

                if (seen && done) {
                    itemEl.classList.add('item-complete');
                } else {
                    itemEl.classList.remove('item-complete');
                }

                // Check if level is now complete
                if (data.level_complete !== undefined) {
                    const section = this.closest('.level-section');
                    const levelNum = parseInt(section.dataset.level);

                    if (data.level_complete) {
                        section.classList.add('complete');
                        const badge = section.querySelector('.level-badge');
                        if (badge) {
                            badge.className = 'level-badge badge-complete';
                            badge.textContent = 'Complete';
                        } else {
                            const titleDiv = section.querySelector('.level-title');
                            const span = document.createElement('span');
                            span.className = 'level-badge badge-complete';
                            span.textContent = 'Complete';
                            titleDiv.appendChild(span);
                        }

                        // Unlock next level if it exists
                        unlockNextLevel(levelNum + 1);
                    } else {
                        section.classList.remove('complete');
                        const badge = section.querySelector('.badge-complete');
                        if (badge) badge.remove();
                    }
                }
            })
            .catch(() => {
                this.checked = !this.checked;
            });
        });
    });

    // Start with levels 2+ collapsed if locked
    document.querySelectorAll('.level-section.locked').forEach(function (s) {
        s.classList.add('collapsed');
    });
});

function unlockNextLevel(nextLevelNum) {
    const next = document.querySelector(`.level-section[data-level="${nextLevelNum}"]`);
    if (!next) return;

    // Only unlock if it was gated by previous level (not by payment)
    if (next.classList.contains('locked')) {
        // Check if this is a payment lock or progression lock
        const badge = next.querySelector('.badge-locked');
        if (badge && badge.textContent === 'Complete previous level') {
            next.classList.remove('locked', 'collapsed');
            badge.remove();
            // Enable checkboxes
            next.querySelectorAll('input[type="checkbox"]').forEach(cb => {
                cb.disabled = false;
            });
        }
        // If payment locked, don't unlock - they need to pay
    }
}
