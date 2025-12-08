<?php

namespace App\Http\Controllers\Storefront;

use App\Core\Controller;
use App\Core\Database;
use App\Tenant\TenantContext;
use App\Support\LangHelper;

class CustomerController extends Controller
{
    private function getCustomerId(): int
    {
        session_start();
        if (!isset($_SESSION['customer_id']) || empty($_SESSION['customer_id'])) {
            $this->redirect('/minha-conta/login');
            exit;
        }
        return (int)$_SESSION['customer_id'];
    }

    public function dashboard(): void
    {
        $customerId = $this->getCustomerId();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar dados do cliente
        $stmt = $db->prepare("
            SELECT * FROM customers 
            WHERE id = :customer_id 
            AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'customer_id' => $customerId,
            'tenant_id' => $tenantId,
        ]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$customer) {
            $this->redirect('/minha-conta/login');
            return;
        }

        // Buscar últimos pedidos (5 mais recentes)
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE customer_id = :customer_id 
            AND tenant_id = :tenant_id 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute([
            'customer_id' => $customerId,
            'tenant_id' => $tenantId,
        ]);
        $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Contar total de pedidos
        $stmt = $db->prepare("
            SELECT COUNT(*) as total FROM pedidos 
            WHERE customer_id = :customer_id 
            AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'customer_id' => $customerId,
            'tenant_id' => $tenantId,
        ]);
        $totalPedidos = $stmt->fetch(\PDO::FETCH_ASSOC)['total'] ?? 0;

        $this->view('storefront/customers/dashboard', [
            'customer' => $customer,
            'pedidos' => $pedidos,
            'totalPedidos' => $totalPedidos,
        ]);
    }

    public function orders(): void
    {
        $customerId = $this->getCustomerId();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar todos os pedidos do cliente
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE customer_id = :customer_id 
            AND tenant_id = :tenant_id 
            ORDER BY created_at DESC
        ");
        $stmt->execute([
            'customer_id' => $customerId,
            'tenant_id' => $tenantId,
        ]);
        $pedidos = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('storefront/customers/orders', [
            'pedidos' => $pedidos,
        ]);
    }

    public function orderShow(string $codigo): void
    {
        $customerId = $this->getCustomerId();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar pedido por número e garantir que pertence ao cliente
        $stmt = $db->prepare("
            SELECT * FROM pedidos 
            WHERE numero_pedido = :numero_pedido 
            AND customer_id = :customer_id 
            AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'numero_pedido' => $codigo,
            'customer_id' => $customerId,
            'tenant_id' => $tenantId,
        ]);
        $pedido = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$pedido) {
            http_response_code(404);
            $this->view('errors/404', ['message' => 'Pedido não encontrado']);
            return;
        }

        // Buscar itens do pedido
        $stmt = $db->prepare("
            SELECT * FROM pedido_itens 
            WHERE pedido_id = :pedido_id 
            AND tenant_id = :tenant_id 
            ORDER BY id ASC
        ");
        $stmt->execute([
            'pedido_id' => $pedido['id'],
            'tenant_id' => $tenantId,
        ]);
        $itens = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('storefront/customers/order-show', [
            'pedido' => $pedido,
            'itens' => $itens,
        ]);
    }

    public function addresses(): void
    {
        $customerId = $this->getCustomerId();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        // Buscar endereços do cliente
        $stmt = $db->prepare("
            SELECT * FROM customer_addresses 
            WHERE customer_id = :customer_id 
            AND tenant_id = :tenant_id 
            ORDER BY is_default DESC, created_at ASC
        ");
        $stmt->execute([
            'customer_id' => $customerId,
            'tenant_id' => $tenantId,
        ]);
        $addresses = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $message = $_SESSION['address_message'] ?? null;
        $messageType = $_SESSION['address_message_type'] ?? 'success';
        unset($_SESSION['address_message'], $_SESSION['address_message_type']);

        // Verificar se está editando um endereço
        $editingAddress = null;
        if (isset($_GET['editar']) && !empty($_GET['editar'])) {
            $addressId = (int)$_GET['editar'];
            $stmt = $db->prepare("
                SELECT * FROM customer_addresses 
                WHERE id = :id 
                AND customer_id = :customer_id 
                AND tenant_id = :tenant_id 
                LIMIT 1
            ");
            $stmt->execute([
                'id' => $addressId,
                'customer_id' => $customerId,
                'tenant_id' => $tenantId,
            ]);
            $editingAddress = $stmt->fetch(\PDO::FETCH_ASSOC);
        }

        $this->view('storefront/customers/addresses', [
            'addresses' => $addresses,
            'message' => $message,
            'messageType' => $messageType,
            'editingAddress' => $editingAddress,
        ]);
    }

    public function saveAddress(): void
    {
        $customerId = $this->getCustomerId();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $id = !empty($_POST['id']) ? (int)$_POST['id'] : null;
        $type = $_POST['type'] ?? 'shipping';
        $street = trim($_POST['street'] ?? '');
        $number = trim($_POST['number'] ?? '');
        $complement = trim($_POST['complement'] ?? '');
        $neighborhood = trim($_POST['neighborhood'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $zipcode = trim($_POST['zipcode'] ?? '');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;

        $errors = [];

        if (empty($street)) $errors[] = 'Logradouro é obrigatório';
        if (empty($neighborhood)) $errors[] = 'Bairro é obrigatório';
        if (empty($city)) $errors[] = 'Cidade é obrigatória';
        if (empty($state) || strlen($state) !== 2) $errors[] = 'Estado é obrigatório (2 caracteres)';
        if (empty($zipcode)) $errors[] = 'CEP é obrigatório';

        if (!empty($errors)) {
            $_SESSION['address_message'] = implode('<br>', $errors);
            $_SESSION['address_message_type'] = 'error';
            $this->redirect('/minha-conta/enderecos');
            return;
        }

        try {
            $db->beginTransaction();

            // Se for padrão, remover padrão de outros endereços
            if ($isDefault) {
                $stmt = $db->prepare("
                    UPDATE customer_addresses 
                    SET is_default = 0 
                    WHERE customer_id = :customer_id 
                    AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'customer_id' => $customerId,
                    'tenant_id' => $tenantId,
                ]);
            }

            if ($id) {
                // Atualizar
                $stmt = $db->prepare("
                    UPDATE customer_addresses 
                    SET type = :type, street = :street, number = :number, 
                        complement = :complement, neighborhood = :neighborhood, 
                        city = :city, state = :state, zipcode = :zipcode, 
                        is_default = :is_default, updated_at = NOW()
                    WHERE id = :id 
                    AND customer_id = :customer_id 
                    AND tenant_id = :tenant_id
                ");
                $stmt->execute([
                    'id' => $id,
                    'customer_id' => $customerId,
                    'tenant_id' => $tenantId,
                    'type' => $type,
                    'street' => $street,
                    'number' => $number ?: null,
                    'complement' => $complement ?: null,
                    'neighborhood' => $neighborhood,
                    'city' => $city,
                    'state' => strtoupper($state),
                    'zipcode' => $zipcode,
                    'is_default' => $isDefault,
                ]);
            } else {
                // Criar
                $stmt = $db->prepare("
                    INSERT INTO customer_addresses (
                        tenant_id, customer_id, type, street, number, complement,
                        neighborhood, city, state, zipcode, is_default, created_at, updated_at
                    ) VALUES (
                        :tenant_id, :customer_id, :type, :street, :number, :complement,
                        :neighborhood, :city, :state, :zipcode, :is_default, NOW(), NOW()
                    )
                ");
                $stmt->execute([
                    'tenant_id' => $tenantId,
                    'customer_id' => $customerId,
                    'type' => $type,
                    'street' => $street,
                    'number' => $number ?: null,
                    'complement' => $complement ?: null,
                    'neighborhood' => $neighborhood,
                    'city' => $city,
                    'state' => strtoupper($state),
                    'zipcode' => $zipcode,
                    'is_default' => $isDefault,
                ]);
            }

            $db->commit();
            $_SESSION['address_message'] = $id ? 'Endereço atualizado com sucesso!' : 'Endereço cadastrado com sucesso!';
            $_SESSION['address_message_type'] = 'success';
        } catch (\Exception $e) {
            $db->rollBack();
            $_SESSION['address_message'] = 'Erro ao salvar endereço: ' . $e->getMessage();
            $_SESSION['address_message_type'] = 'error';
        }

        $this->redirect('/minha-conta/enderecos');
    }

    public function deleteAddress(int $id): void
    {
        $customerId = $this->getCustomerId();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            DELETE FROM customer_addresses 
            WHERE id = :id 
            AND customer_id = :customer_id 
            AND tenant_id = :tenant_id
        ");
        $stmt->execute([
            'id' => $id,
            'customer_id' => $customerId,
            'tenant_id' => $tenantId,
        ]);

        $_SESSION['address_message'] = 'Endereço removido com sucesso!';
        $_SESSION['address_message_type'] = 'success';
        $this->redirect('/minha-conta/enderecos');
    }

    public function profile(): void
    {
        session_start();
        $customerId = $this->getCustomerId();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $stmt = $db->prepare("
            SELECT * FROM customers 
            WHERE id = :customer_id 
            AND tenant_id = :tenant_id 
            LIMIT 1
        ");
        $stmt->execute([
            'customer_id' => $customerId,
            'tenant_id' => $tenantId,
        ]);
        $customer = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$customer) {
            $this->redirect('/minha-conta/login');
            return;
        }

        $message = $_SESSION['profile_message'] ?? null;
        $messageType = $_SESSION['profile_message_type'] ?? 'success';
        unset($_SESSION['profile_message'], $_SESSION['profile_message_type']);

        $this->view('storefront/customers/profile', [
            'customer' => $customer,
            'message' => $message,
            'messageType' => $messageType,
        ]);
    }

    public function updateProfile(): void
    {
        $customerId = $this->getCustomerId();
        $tenantId = TenantContext::id();
        $db = Database::getConnection();

        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $document = trim($_POST['document'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $errors = [];

        if (empty($name)) {
            $errors[] = 'Nome é obrigatório';
        }

        if (!empty($password)) {
            if (strlen($password) < 6) {
                $errors[] = 'Senha deve ter no mínimo 6 caracteres';
            }
            if ($password !== $passwordConfirm) {
                $errors[] = 'As senhas não coincidem';
            }
        }

        if (!empty($errors)) {
            $_SESSION['profile_message'] = implode('<br>', $errors);
            $_SESSION['profile_message_type'] = 'error';
            $this->redirect('/minha-conta/perfil');
            return;
        }

        try {
            $updateFields = [
                'name' => $name,
                'phone' => $phone ?: null,
                'document' => $document ?: null,
                'updated_at' => 'NOW()',
            ];

            if (!empty($password)) {
                $updateFields['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }

            $setClause = [];
            $params = [
                'customer_id' => $customerId,
                'tenant_id' => $tenantId,
            ];

            foreach ($updateFields as $field => $value) {
                if ($value === 'NOW()') {
                    $setClause[] = "$field = NOW()";
                } else {
                    $setClause[] = "$field = :$field";
                    $params[$field] = $value;
                }
            }

            $sql = "UPDATE customers SET " . implode(', ', $setClause) . " 
                    WHERE id = :customer_id AND tenant_id = :tenant_id";
            
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Atualizar sessão
            $_SESSION['customer_name'] = $name;

            $_SESSION['profile_message'] = 'Dados atualizados com sucesso!';
            $_SESSION['profile_message_type'] = 'success';
        } catch (\Exception $e) {
            $_SESSION['profile_message'] = 'Erro ao atualizar dados: ' . $e->getMessage();
            $_SESSION['profile_message_type'] = 'error';
        }

        $this->redirect('/minha-conta/perfil');
    }
}


