            <!-- Aquí termina el contenido de la página -->
            </div> <!-- Fin de content-wrapper -->
            
            <footer class="footer">
                <div class="footer-content">
                    <p>&copy; <?php echo date('Y'); ?> - Sistema de Gestión Escolar</p>
                </div>
            </footer>
        </div> <!-- Fin de main-content -->
    </div> <!-- Fin de admin-container -->

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    
    <!-- Componentes principales -->
    <script src="<?php echo $profesor_base_url; ?>/assets/js/components/sidebar.js"></script>
    <script src="<?php echo $profesor_base_url; ?>/assets/js/components/topbar.js"></script>
    
    <!-- Sweetalert2 para alertas más bonitas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Script para mostrar mensajes de éxito o error -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        <?php if (isset($_SESSION['mensaje_exito'])): ?>
            Swal.fire({
                title: '¡Éxito!',
                text: '<?php echo $_SESSION['mensaje_exito']; ?>',
                icon: 'success',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Aceptar'
            });
            <?php unset($_SESSION['mensaje_exito']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['mensaje_error'])): ?>
            Swal.fire({
                title: 'Error',
                text: '<?php echo $_SESSION['mensaje_error']; ?>',
                icon: 'error',
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'Aceptar'
            });
            <?php unset($_SESSION['mensaje_error']); ?>
        <?php endif; ?>
    });
    </script>
    
    <!-- Scripts específicos de la página (si se definen) -->
    <?php if (isset($pageScripts)): ?>
    <script><?php echo $pageScripts; ?></script>
    <?php endif; ?>
</body>
</html>