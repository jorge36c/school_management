<?php
if (!isset($sede) || !isset($estadisticas)) {
   die('Error: No se han proporcionado los datos necesarios');
}
?>

<div class="dashboard-stats">
   <div class="stats-header">
       <h2>
           <i class="fas fa-chart-bar"></i>
           Estadísticas Generales
       </h2>
       <div class="stats-actions">
           <span class="last-update">
               <i class="fas fa-sync-alt"></i>
               Actualizado: <?php echo date('d/m/Y H:i'); ?>
           </span>
           <button onclick="actualizarEstadisticas()" class="btn-refresh">
               <i class="fas fa-sync"></i>
           </button>
       </div>
   </div>

   <div class="stats-grid">
       <!-- Estudiantes -->
       <div class="stat-card" onclick="verDetalleEstudiantes(<?php echo $sede['id']; ?>)">
           <div class="stat-icon bg-primary">
               <i class="fas fa-user-graduate"></i>
           </div>
           <div class="stat-info">
               <div class="stat-value" id="stat-estudiantes">
                   <?php echo $estadisticas['estudiantes']['total']; ?>
               </div>
               <div class="stat-label">Estudiantes</div>
           </div>
       </div>

       <!-- Profesores -->
       <div class="stat-card" onclick="verDetalleProfesores(<?php echo $sede['id']; ?>)">
           <div class="stat-icon bg-success">
               <i class="fas fa-chalkboard-teacher"></i>
           </div>
           <div class="stat-info">
               <div class="stat-value" id="stat-profesores">
                   <?php echo $estadisticas['profesores']['total']; ?>
               </div>
               <div class="stat-label">Profesores</div>
           </div>
       </div>

       <!-- Grupos -->
       <div class="stat-card" onclick="verDetalleGrupos(<?php echo $sede['id']; ?>)">
           <div class="stat-icon bg-warning">
               <i class="fas fa-users"></i>
           </div>
           <div class="stat-info">
               <div class="stat-value" id="stat-grupos">
                   <?php echo $estadisticas['grupos']['total']; ?>
               </div>
               <div class="stat-label">Grupos Activos</div>
           </div>
       </div>

       <!-- Matrículas -->
       <div class="stat-card" onclick="verDetalleMatriculas(<?php echo $sede['id']; ?>)">
           <div class="stat-icon bg-info">
               <i class="fas fa-file-signature"></i>
           </div>
           <div class="stat-info">
               <div class="stat-value" id="stat-matriculas">
                   <?php echo $estadisticas['matriculas']['total']; ?>
               </div>
               <div class="stat-label">Matrículas</div>
           </div>
       </div>
   </div>
</div>

<style>
.dashboard-stats {
   padding: 1.5rem;
   background: white;
   border-radius: 1rem;
   box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.stats-header {
   display: flex;
   justify-content: space-between;
   align-items: center;
   margin-bottom: 2rem;
   padding-bottom: 1rem;
   border-bottom: 1px solid #e5e7eb;
}

.stats-header h2 {
   font-size: 1.25rem;
   font-weight: 600;
   display: flex;
   align-items: center;
   gap: 0.75rem;
   margin: 0;
   color: #1e293b;
}

.stats-actions {
   display: flex;
   align-items: center;
   gap: 1rem;
}

.last-update {
   font-size: 0.875rem;
   color: #64748b;
   display: flex;
   align-items: center;
   gap: 0.5rem;
}

.btn-refresh {
   background: none;
   border: none;
   color: #3b82f6;
   cursor: pointer;
   padding: 0.5rem;
   border-radius: 0.5rem;
   transition: all 0.2s ease;
}

.btn-refresh:hover {
   background: #eff6ff;
   transform: rotate(180deg);
}

.stats-grid {
   display: grid;
   grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
   gap: 1.5rem;
}

.stat-card {
   background: white;
   border-radius: 1rem;
   padding: 1.5rem;
   display: flex;
   align-items: center;
   gap: 1rem;
   box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
   transition: all 0.3s ease;
   cursor: pointer;
   border: 1px solid #e5e7eb;
}

.stat-card:hover {
   transform: translateY(-4px) scale(1.02);
   box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.stat-icon {
   width: 48px;
   height: 48px;
   border-radius: 12px;
   display: flex;
   align-items: center;
   justify-content: center;
   font-size: 1.25rem;
   color: white;
   transition: transform 0.3s ease;
}

.stat-card:hover .stat-icon {
   transform: scale(1.1) rotate(5deg);
}

.bg-primary { background: #3b82f6; }
.bg-success { background: #22c55e; }
.bg-warning { background: #f59e0b; }
.bg-info { background: #06b6d4; }

.stat-info {
   flex: 1;
}

@keyframes pulse {
   0% { transform: scale(1); }
   50% { transform: scale(1.05); }
   100% { transform: scale(1); }
}

.stat-value {
   font-size: 1.5rem;
   font-weight: 600;
   color: #1e293b;
   line-height: 1;
   margin-bottom: 0.25rem;
   transition: all 0.3s ease;
}

.stat-value.updating {
   animation: pulse 0.6s ease;
   color: #3b82f6;
}

.stat-label {
   font-size: 0.875rem;
   color: #64748b;
}

@media (max-width: 768px) {
   .stats-grid {
       grid-template-columns: repeat(2, 1fr);
   }
   
   .stats-header {
       flex-direction: column;
       align-items: flex-start;
       gap: 1rem;
   }
}

@media (max-width: 640px) {
   .stats-grid {
       grid-template-columns: 1fr;
   }
   
   .stat-card {
       padding: 1rem;
   }
}
</style>

<script>
// Función para actualizar estadísticas
async function actualizarEstadisticas() {
   const button = document.querySelector('.btn-refresh');
   button.style.transform = 'rotate(360deg)';
   
   try {
       const response = await fetch(`/school_management/api/estadisticas.php?sede_id=<?php echo $sede['id']; ?>`);
       const data = await response.json();
       
       if (data.success) {
           actualizarValor('estudiantes', data.estadisticas.estudiantes.total);
           actualizarValor('profesores', data.estadisticas.profesores.total);
           actualizarValor('grupos', data.estadisticas.grupos.total);
           actualizarValor('matriculas', data.estadisticas.matriculas.total);
           
           document.querySelector('.last-update').textContent = 
               `Actualizado: ${new Date().toLocaleString()}`;
       }
   } catch (error) {
       console.error('Error al actualizar estadísticas:', error);
   }
   
   setTimeout(() => {
       button.style.transform = 'rotate(0deg)';
   }, 500);
}

function actualizarValor(tipo, nuevoValor) {
   const elemento = document.getElementById(`stat-${tipo}`);
   if (!elemento) return;
   
   elemento.classList.add('updating');
   elemento.textContent = nuevoValor;
   
   setTimeout(() => {
       elemento.classList.remove('updating');
   }, 600);
}

function verDetalleEstudiantes(sedeId) {
   window.location.href = `/school_management/admin/users/list_students.php?sede_id=${sedeId}`;
}

function verDetalleProfesores(sedeId) {
   window.location.href = `/school_management/admin/users/list_teachers.php?sede_id=${sedeId}`;
}

function verDetalleGrupos(sedeId) {
   window.location.href = `/school_management/admin/academic/headquarters/list_groups.php?sede_id=${sedeId}`;
}

function verDetalleMatriculas(sedeId) {
   window.location.href = `/school_management/admin/academic/matriculas/list_matriculas.php?sede_id=${sedeId}`;
}
</script>