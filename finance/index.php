<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

// ?????? ???????
$type = isset($_GET['type']) ? cleanInput($_GET['type']) : '';
$account = isset($_GET['account']) ? intval($_GET['account']) : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-t');

// Query ????
$sql = "SELECT ft.*, 
        c.name as client_name,
        p.name as project_name,
        ba.name as account_name
        FROM finance_transactions ft
        LEFT JOIN clients c ON ft.client_id = c.id
        LEFT JOIN projects p ON ft.project_id = p.id
        LEFT JOIN bank_accounts ba ON ft.bank_account_id = ba.id
        WHERE 1=1";

$params = [];

// ???????
if ($type) {
    $sql .= " AND ft.transaction_type = :type";
    $params['type'] = $type;
}

if ($account) {
    $sql .= " AND ft.bank_account_id = :account";
    $params['account'] = $account;
}

if ($date_from) {
    $sql .= " AND ft.transaction_date >= :date_from";
    $params['date_from'] = $date_from;
}

if ($date_to) {
    $sql .= " AND ft.transaction_date <= :date_to";
    $params['date_to'] = $date_to;
}

$sql .= " ORDER BY ft.transaction_date DESC, ft.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// ?????? ???????? - ??? ???? PDO ?? ????? ?????????
$totals = $db->prepare("SELECT 
    SUM(CASE WHEN transaction_type = '??????' THEN amount ELSE 0 END) as total_income,
    SUM(CASE WHEN transaction_type = '?????' THEN amount ELSE 0 END) as total_expense
    FROM finance_transactions
    WHERE 1=1
    " . ($type ? "AND transaction_type = :type2" : "") . "
    " . ($account ? "AND bank_account_id = :account2" : "") . "
    AND transaction_date >= :date_from2
    AND transaction_date <= :date_to2");

// ????? ????????? ???? query ???
$params2 = [];
if ($type) $params2['type2'] = $type;
if ($account) $params2['account2'] = $account;
$params2['date_from2'] = $date_from;
$params2['date_to2'] = $date_to;

$totals->execute($params2);
$summary = $totals->fetch();

// ?????? ???? ???????
$accounts = $db->query("SELECT id, name FROM bank_accounts ORDER BY name")->fetchAll();
?>