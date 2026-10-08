<?php
/**
 * Dashboard Layout - Standalone Footer & Interactive Controls
 *
 * @package CubaInvestment\Core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
</div><!-- /#dashboard-app -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Mobile Off-Canvas Drawer Toggle
    const mobileDrawer = document.getElementById('mobile-sidebar-drawer');
    const mobileOpenBtn = document.getElementById('mobile-drawer-open');
    const mobileCloseBtn = document.getElementById('mobile-drawer-close');
    const mobileBackdrop = document.getElementById('mobile-drawer-backdrop');

    function openMobileDrawer() {
        if (!mobileDrawer) return;
        mobileDrawer.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileDrawer() {
        if (!mobileDrawer) return;
        mobileDrawer.classList.add('hidden');
        document.body.style.overflow = '';
    }

    if (mobileOpenBtn) mobileOpenBtn.addEventListener('click', openMobileDrawer);
    if (mobileCloseBtn) mobileCloseBtn.addEventListener('click', closeMobileDrawer);
    if (mobileBackdrop) mobileBackdrop.addEventListener('click', closeMobileDrawer);

    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileDrawer();
            closeUserDropdown();
        }
    });

    // 2. Desktop Sidebar Collapse Toggle
    const collapseBtn = document.getElementById('sidebar-collapse-toggle');
    const appContainer = document.getElementById('dashboard-app');
    const isCollapsedStored = localStorage.getItem('cin_sidebar_collapsed') === 'true';

    if (isCollapsedStored && appContainer) {
        appContainer.classList.add('dashboard-collapsed');
    }

    if (collapseBtn && appContainer) {
        collapseBtn.addEventListener('click', function() {
            appContainer.classList.toggle('dashboard-collapsed');
            const isNowCollapsed = appContainer.classList.contains('dashboard-collapsed');
            localStorage.setItem('cin_sidebar_collapsed', isNowCollapsed);
        });
    }

    // 3. User Account Dropdown Toggle
    const userDropdownBtn = document.getElementById('user-dropdown-button');
    const userDropdownMenu = document.getElementById('user-dropdown-menu');

    function closeUserDropdown() {
        if (!userDropdownMenu) return;
        userDropdownMenu.classList.add('hidden');
        if (userDropdownBtn) userDropdownBtn.setAttribute('aria-expanded', 'false');
    }

    if (userDropdownBtn && userDropdownMenu) {
        userDropdownBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isHidden = userDropdownMenu.classList.contains('hidden');
            if (isHidden) {
                userDropdownMenu.classList.remove('hidden');
                userDropdownBtn.setAttribute('aria-expanded', 'true');
            } else {
                closeUserDropdown();
            }
        });

        document.addEventListener('click', function(e) {
            if (!userDropdownMenu.contains(e.target) && !userDropdownBtn.contains(e.target)) {
                closeUserDropdown();
            }
        });
    }

    // 4. Image Upload Instant Preview
    const imageInputs = document.querySelectorAll('input[type="file"][data-preview-target]');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const targetId = this.getAttribute('data-preview-target');
            const targetEl = document.getElementById(targetId);
            if (!targetEl || !this.files || !this.files[0]) return;

            const file = this.files[0];
            if (!file.type.match('image.*')) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                if (targetEl.tagName === 'IMG') {
                    targetEl.src = e.target.result;
                    targetEl.classList.remove('hidden');
                } else {
                    targetEl.style.backgroundImage = 'url(' + e.target.result + ')';
                    targetEl.style.backgroundSize = 'cover';
                }
            };
            reader.readAsDataURL(file);
        });
    });
});
</script>

<?php wp_footer(); ?>
</body>
</html>
