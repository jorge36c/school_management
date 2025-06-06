<?php
// Definir base_url si no está definido
if (!isset($profesor_base_url)) {
    $profesor_base_url = '/school_management/profesor';
}
?>
    <!-- Scripts comunes -->
    <script src="<?php echo $profesor_base_url; ?>/assets/js/components/sidebar.js"></script>
    <script src="<?php echo $profesor_base_url; ?>/assets/js/components/topbar.js"></script>
    
    <!-- Scripts específicos de la página -->
    <?php if (isset($page_scripts) && is_array($page_scripts)): ?>
        <?php foreach ($page_scripts as $script): ?>
            <script src="<?php echo $profesor_base_url; ?>/assets/js/<?php echo $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>