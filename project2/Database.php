<?php
class Database {
    private $dataFile = '../data/items.json';
    private $items = [];

    public function __construct() {
        $this->loadData();
    }

    private function loadData() {
        if (file_exists($this->dataFile)) {
            $json = file_get_contents($this->dataFile);
            $decoded = json_decode($json, true);
            $this->items = is_array($decoded) ? $decoded : [];
        }
        if (empty($this->items)) {
            $this->items = [
                ['id' => 1, 'name' => 'Laptop', 'category' => 'Electronics', 'price' => 999.99, 'inStock' => true],
                ['id' => 2, 'name' => 'Coffee Mug', 'category' => 'Kitchen', 'price' => 12.99, 'inStock' => true],
                ['id' => 3, 'name' => 'Notebook', 'category' => 'Office', 'price' => 4.99, 'inStock' => false]
            ];
            $this->saveData();
        }
    }

    private function saveData() {
        $dir = dirname($this->dataFile);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents($this->dataFile, json_encode($this->items, JSON_PRETTY_PRINT));
    }

    private function getNextId() {
        $max = 0;
        foreach ($this->items as $item) if ($item['id'] > $max) $max = $item['id'];
        return $max + 1;
    }

    public function getAllItems() { return $this->items; }

    public function getItemById($id) {
        foreach ($this->items as $item) if ($item['id'] == $id) return $item;
        return null;
    }

    public function createItem($data) {
        $new = [
            'id' => $this->getNextId(),
            'name' => trim($data['name']),
            'category' => trim($data['category']),
            'price' => (float)$data['price'],
            'inStock' => isset($data['inStock']) ? (bool)$data['inStock'] : true,
            'createdAt' => date('Y-m-d H:i:s')
        ];
        $this->items[] = $new;
        $this->saveData();
        return $new;
    }

    public function updateItem($id, $data) {
        foreach ($this->items as &$item) {
            if ($item['id'] == $id) {
                $item['name'] = trim($data['name']);
                $item['category'] = trim($data['category']);
                $item['price'] = (float)$data['price'];
                if (isset($data['inStock'])) $item['inStock'] = (bool)$data['inStock'];
                $item['updatedAt'] = date('Y-m-d H:i:s');
                $this->saveData();
                return $item;
            }
        }
        return null;
    }

    public function deleteItem($id) {
        foreach ($this->items as $key => $item) {
            if ($item['id'] == $id) {
                $deleted = $item;
                unset($this->items[$key]);
                $this->items = array_values($this->items);
                $this->saveData();
                return $deleted;
            }
        }
        return null;
    }
}
?>