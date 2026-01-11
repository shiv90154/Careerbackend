<?php
require_once 'response.php';
require_once 'security.php';

class Validator {
    private $data;
    private $errors = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    /**
     * Validate required fields
     */
    public function required($fields) {
        foreach ($fields as $field) {
            if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        return $this;
    }
    
    /**
     * Validate email format
     */
    public function email($field) {
        if (isset($this->data[$field])) {
            $email = filter_var(trim($this->data[$field]), FILTER_VALIDATE_EMAIL);
            if (!$email) {
                $this->errors[$field] = 'Invalid email format';
            }
        }
        return $this;
    }
    
    /**
     * Validate password strength
     */
    public function password($field) {
        if (isset($this->data[$field])) {
            try {
                SecurityManager::validatePassword($this->data[$field]);
            } catch (Exception $e) {
                $this->errors[$field] = $e->getMessage();
            }
        }
        return $this;
    }
    
    /**
     * Validate string length
     */
    public function length($field, $min = null, $max = null) {
        if (isset($this->data[$field])) {
            $length = strlen(trim($this->data[$field]));
            
            if ($min !== null && $length < $min) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least $min characters";
            }
            
            if ($max !== null && $length > $max) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed $max characters";
            }
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($field, $min = null, $max = null) {
        if (isset($this->data[$field])) {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number';
            } else {
                $value = (float) $this->data[$field];
                
                if ($min !== null && $value < $min) {
                    $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least $min";
                }
                
                if ($max !== null && $value > $max) {
                    $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed $max";
                }
            }
        }
        return $this;
    }
    
    /**
     * Validate integer value
     */
    public function integer($field, $min = null, $max = null) {
        if (isset($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_INT)) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be an integer';
            } else {
                $value = (int) $this->data[$field];
                
                if ($min !== null && $value < $min) {
                    $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least $min";
                }
                
                if ($max !== null && $value > $max) {
                    $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must not exceed $max";
                }
            }
        }
        return $this;
    }
    
    /**
     * Validate phone number (Indian format)
     */
    public function phone($field) {
        if (isset($this->data[$field])) {
            $phone = preg_replace('/[^0-9]/', '', $this->data[$field]);
            
            if (!preg_match('/^[6-9]\d{9}$/', $phone)) {
                $this->errors[$field] = 'Invalid phone number format';
            }
        }
        return $this;
    }
    
    /**
     * Validate date format
     */
    public function date($field, $format = 'Y-m-d') {
        if (isset($this->data[$field])) {
            $date = DateTime::createFromFormat($format, $this->data[$field]);
            
            if (!$date || $date->format($format) !== $this->data[$field]) {
                $this->errors[$field] = "Invalid date format. Expected format: $format";
            }
        }
        return $this;
    }
    
    /**
     * Validate enum values
     */
    public function enum($field, $allowedValues) {
        if (isset($this->data[$field])) {
            if (!in_array($this->data[$field], $allowedValues)) {
                $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be one of: ' . implode(', ', $allowedValues);
            }
        }
        return $this;
    }
    
    /**
     * Validate URL format
     */
    public function url($field) {
        if (isset($this->data[$field])) {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
                $this->errors[$field] = 'Invalid URL format';
            }
        }
        return $this;
    }
    
    /**
     * Custom validation rule
     */
    public function custom($field, $callback, $message) {
        if (isset($this->data[$field])) {
            if (!$callback($this->data[$field])) {
                $this->errors[$field] = $message;
            }
        }
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Validate and return sanitized data or throw validation error
     */
    public function validate() {
        if ($this->fails()) {
            ApiResponse::validationError($this->errors);
        }
        
        // Return sanitized data
        $sanitized = [];
        foreach ($this->data as $key => $value) {
            $sanitized[$key] = SecurityManager::sanitizeInput($value);
        }
        
        return $sanitized;
    }
}