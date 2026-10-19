        </main>

    </div>

</div>


<script>

    lucide.createIcons();


    function openSidebar() {

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');

    }


    function closeSidebar() {

        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');

        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');

    }


    window.addEventListener('resize', function () {

        if (window.innerWidth >= 1024) {
            closeSidebar();
        }

    });

</script>

</body>

</html>