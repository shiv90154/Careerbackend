<?php

class Validator {
    private $data;
    private $errors = [];
    private $rules = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function required($fields) {
        if (!is_array($fields)) {
            $fields = [$fields];
        }
        
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
                $this->errors[$field] = ucfirst($field) . ' is required';
            }
        }
        
        return $this;
    }
    
    public function email($field) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = 'Invalid email format';
        }
        
        return $this;
    }
    
    public function length($field, $min = null, $max = null) {
        if (isset($this->data[$field])) {
            $length = strlen($this->data[$field]);
            
            if ($min !== null && $length < $min) {
                $this->errors[$field] = ucfirst($field) . " must be at least {$min} characters";
            }
            
            if ($max !== null && $length > $max) {
                $this->errors[$field] = ucfirst($field) . " must not exceed {$max} characters";
            }
        }
        
        return $this;
    }
    
    public function numeric($field) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->errors[$field] = ucfirst($field) . ' must be numeric';
        }
        
        return $this;
    }
    
    public function integer($field) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
            $this->errors[$field] = ucfirst($field) . ' must be an integer';
        }
        
        return $this;
    }
    
    public function in($field, $values) {
        if (isset($this->data[$field]) && !in_array($this->data[$field], $values)) {
            $this->errors[$field] = ucfirst($field) . ' must be one of: ' . implode(', ', $values);
        }
        
        return $this;
    }
    
    public function unique($field, $table, $column = null, $excludeId = null) {
        if (!isset($this->data[$field])) {
            return $this;
        }
        
        $column = $column ?: $field;
        $db = (new Database())->getConnection();
        
        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $params = [$this->data[$field]];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        
        if ($stmt->fetchColumn() > 0) {
            $this->errors[$field] = ucfirst($field) . ' already exists';
        }
        
        return $this;
    }
    
    public function match($field, $matchField) {
        if (isset($this->data[$field]) && isset($this->data[$matchField])) {
            if ($this->data[$field] !== $this->data[$matchField]) {
                $this->errors[$field] = ucfirst($field) . ' must match ' . $matchField;
            }
        }
        
        return $this;
    }
    
    public function validate() {
        if (!empty($this->errors)) {
            ApiResponse::validationError($this->errors);
        }
        
        return SecurityManager::sanitizeInput($this->data);
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
}
?>