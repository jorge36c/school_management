<?php
/**
 * SimpleXLSX - Librería PHP para lectura de archivos XLSX
 *
 * Esta es una versión simplificada de la biblioteca SimpleXLSX
 * Original por Sergey Shuchkin (SHUCHKIN) <sergey.shuchkin@gmail.com>
 * 
 * Adaptado para el sistema de gestión escolar
 */

class SimpleXLSX {
    public $sheets = [];
    protected $sheetNames = [];
    protected $sheetFiles = [];
    protected $styles = [];
    protected $hyperlinks = [];
    protected $package = [
        'filename' => '',
        'mtime' => 0,
        'size' => 0,
        'comment' => '',
        'entries' => []
    ];
    protected $sharedstrings = [];
    protected static $error = false;
    protected $workbook = false;
    protected $debug = false;

    // Crear instancia a partir de un archivo
    public static function parse($filename, $is_data = false, $debug = false) {
        $xlsx = new self();
        $xlsx->debug = $debug;
        if ($is_data) {
            $xlsx->_parseData($filename);
        } else {
            $xlsx->_parseFile($filename);
        }
        if (self::$error) {
            return false;
        }
        return $xlsx;
    }

    // Obtener mensaje de error
    public static function parseError() {
        return self::$error;
    }

    // Obtener filas de la primera hoja o hoja específica
    public function rows($worksheet_id = 0) {
        if (isset($this->sheets[$worksheet_id])) {
            $rows = &$this->sheets[$worksheet_id];
            return $rows;
        }
        return false;
    }

    // Obtener nombres de hojas
    public function sheetNames() {
        return $this->sheetNames;
    }

    // Obtener nombre de hoja por ID
    public function sheetName($worksheet_id) {
        if (isset($this->sheetNames[$worksheet_id])) {
            return $this->sheetNames[$worksheet_id];
        }
        return false;
    }

    // Parsear archivo XLSX
    protected function _parseFile($filename) {
        self::$error = false;
        
        if (!is_readable($filename)) {
            self::$error = 'File not found or not readable';
            return false;
        }
        
        $this->package['filename'] = $filename;
        $this->package['mtime'] = filemtime($filename);
        $this->package['size'] = filesize($filename);
        
        // Abrir archivo ZIP
        $zip = new ZipArchive();
        if (true !== $zip->open($filename)) {
            self::$error = 'Unable to open the ZIP file';
            return false;
        }
        
        // Procesar contenido del archivo
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            $this->package['entries'][] = $entryName;
        }
        
        // Encontrar el archivo workbook.xml
        if (($workbookIndex = $this->_locateFile('xl/workbook.xml')) === false) {
            self::$error = "workbook.xml not found in the XLSX file";
            $zip->close();
            return false;
        }
        
        $this->workbook = $zip->getFromIndex($workbookIndex);
        
        // Encontrar el archivo sharedStrings.xml si existe
        if (($sharedStringsIndex = $this->_locateFile('xl/sharedStrings.xml')) !== false) {
            $this->sharedstrings = $this->_parseSharedStrings($zip->getFromIndex($sharedStringsIndex));
        }
        
        // Leer nombres de hojas y referenciar archivos de hojas
        $workbookXML = $this->_parseWorkbook($this->workbook);
        
        // Procesar cada hoja
        foreach ($workbookXML['sheets'] as $sheetIndex => $sheetInfo) {
            $sheetID = $sheetInfo['id'];
            $sheetName = $sheetInfo['name'];
            $this->sheetNames[$sheetIndex] = $sheetName;
            
            $sheetFilename = 'xl/worksheets/sheet' . $sheetID . '.xml';
            $sheetFileIndex = $this->_locateFile($sheetFilename);
            
            if ($sheetFileIndex !== false) {
                $this->sheetFiles[$sheetIndex] = $sheetFileIndex;
                $sheetXML = $zip->getFromIndex($sheetFileIndex);
                $this->sheets[$sheetIndex] = $this->_parseSheet($sheetXML);
            }
        }
        
        $zip->close();
        return true;
    }

    // Localizar archivo dentro del ZIP
    protected function _locateFile($filename) {
        foreach ($this->package['entries'] as $index => $entryName) {
            if ($entryName === $filename) {
                return $index;
            }
        }
        return false;
    }

    // Parsear archivo sharedStrings.xml
    protected function _parseSharedStrings($xml) {
        $strings = [];
        $sxml = new SimpleXMLElement($xml);
        
        foreach ($sxml->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
            } elseif (isset($si->r)) {
                $text = '';
                foreach ($si->r as $r) {
                    if (isset($r->t)) {
                        $text .= (string) $r->t;
                    }
                }
                $strings[] = $text;
            }
        }
        
        return $strings;
    }

    // Parsear archivo workbook.xml
    protected function _parseWorkbook($xml) {
        $result = [
            'sheets' => []
        ];
        
        $sxml = new SimpleXMLElement($xml);
        
        foreach ($sxml->sheets->sheet as $sheet) {
            $attrs = $sheet->attributes();
            $name = (string) $attrs['name'];
            $sheetId = (string) $attrs['sheetId'];
            
            $result['sheets'][] = [
                'name' => $name,
                'id' => $sheetId
            ];
        }
        
        return $result;
    }

    // Parsear archivo sheet[N].xml
    protected function _parseSheet($xml) {
        $rows = [];
        $sxml = new SimpleXMLElement($xml);
        $cells = [];
        
        // Recopilar todas las celdas
        if (isset($sxml->sheetData->row)) {
            foreach ($sxml->sheetData->row as $row) {
                foreach ($row->c as $c) {
                    $attrs = $c->attributes();
                    $address = (string) $attrs['r'];
                    list($col, $rowNum) = $this->_parseAddress($address);
                    
                    $value = '';
                    if (isset($c->v)) {
                        $value = (string) $c->v;
                    }
                    
                    // Determinar tipo de celda
                    $cellType = isset($attrs['t']) ? (string) $attrs['t'] : '';
                    
                    // Si es string compartido, obtener el valor real
                    if ($cellType === 's' && isset($this->sharedstrings[$value])) {
                        $value = $this->sharedstrings[$value];
                    }
                    
                    $cells[$rowNum][$col] = $value;
                }
            }
        }
        
        // Ordenar filas y columnas
        ksort($cells);
        foreach ($cells as $rowIndex => $row) {
            ksort($row);
            $rows[] = array_values($row);
        }
        
        return $rows;
    }

    // Parsear dirección de celda (ej: A1, B2, etc.)
    protected function _parseAddress($address) {
        if (preg_match('/([A-Z]+)(\d+)/', $address, $matches)) {
            $col = $this->_getColumnIndex($matches[1]);
            $row = (int) $matches[2];
            return [$col, $row];
        }
        return [0, 0];
    }

    // Convertir letras de columna a índice
    protected function _getColumnIndex($colString) {
        $colIndex = 0;
        $colString = strtoupper($colString);
        $length = strlen($colString);
        
        for ($i = 0; $i < $length; $i++) {
            $colIndex = $colIndex * 26 + (ord($colString[$i]) - 64);
        }
        
        return $colIndex - 1; // 0-based index
    }

    // Parsear datos directos (no implementado, solo para compatibilidad)
    protected function _parseData($data) {
        self::$error = 'Direct data parsing not implemented';
        return false;
    }

    // Liberar recursos
    public function __destruct() {
        $this->sheets = [];
        $this->sheetNames = [];
        $this->sheetFiles = [];
        $this->styles = [];
        $this->hyperlinks = [];
        $this->package = [
            'filename' => '',
            'mtime' => 0,
            'size' => 0,
            'comment' => '',
            'entries' => []
        ];
        $this->sharedstrings = [];
    }
}
?>