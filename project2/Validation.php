<?php
class Validation {
    public function validateItem($data) {
        $errors = [];
        if (!isset($data['name']) || trim($data['name']) === '') {
            $errors[] = 'Name is required';
        } elseif (strlen(trim($data['name'])) < 2) {
            $errors[] = 'Name must be at least 2 characters';
        } elseif (strlen(trim($data['name'])) > 50) {
            $errors[] = 'Name too long (max 50)';
        }

        if (!isset($data['category']) || trim($data['category']) === '') {
            $errors[] = 'Category is required';
        }

        if (!isset($data['price']) || $data['price'] === '') {
            $errors[] = 'Price is required';
        } elseif (!is_numeric($data['price'])) {
            $errors[] = 'Price must be a number';
        } elseif ((float)$data['price'] < 0) {
            $errors[] = 'Price cannot be negative';
        }

        if (isset($data['inStock']) && !is_bool($data['inStock']) && $data['inStock'] !== 'true' && $data['inStock'] !== 'false') {
            $errors[] = 'inStock must be true or false';
        }
        return $errors;
    }
}
?>