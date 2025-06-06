<?php
class Sede {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    public function getAll() {
        $sql = "SELECT s.*, 
                (SELECT COUNT(*) FROM estudiantes e WHERE e.sede_id = s.id) as total_estudiantes,
                (SELECT COUNT(*) FROM profesores p WHERE p.sede_id = s.id) as total_profesores,
                (SELECT COUNT(*) FROM grupos g WHERE g.sede_id = s.id) as total_grupos
                FROM sedes s ORDER BY s.nombre";
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'activo' THEN 1 ELSE 0 END) as activas,
                SUM(CASE WHEN tipo_ensenanza = 'multigrado' THEN 1 ELSE 0 END) as multigrado
                FROM sedes";
        return $this->db->query($sql)->fetch();
    }
} 