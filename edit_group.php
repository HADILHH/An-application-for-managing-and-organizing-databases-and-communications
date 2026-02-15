<?php
include 'config.php';

if(!isset($_GET['group_id'])){
    header("Location: admin.php");
    exit;
}

$group_id = intval($_GET['group_id']);

// =====================
// Mise à jour Groupe
// =====================
if(isset($_POST['update_group'])){
    $name = htmlspecialchars($_POST['group_name']);
    $desc = htmlspecialchars($_POST['group_desc']);
    $stmt = $conn->prepare("UPDATE groups SET name=?, description=? WHERE id=?");
    $stmt->bind_param("ssi", $name, $desc, $group_id);
    $stmt->execute();
    header("Location: admin.php");
    exit;
}

// =====================
// Récupérer info Groupe
// =====================
$stmt = $conn->prepare("SELECT name, description FROM groups WHERE id=?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows==0){
    echo "Groupe non trouvé";
    exit;
}
$group = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Modifier Groupe</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
<div class="container">
    <h3>✏️ Modifier Groupe</h3>
    <form method="POST" class="mt-3">
        <div class="mb-3">
            <label>Nom du groupe</label>
            <input type="text" name="group_name" class="form-control" value="<?= htmlspecialchars($group['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label>Description</label>
            <input type="text" name="group_desc" class="form-control" value="<?= htmlspecialchars($group['description']) ?>">
        </div>
        <button type="submit" name="update_group" class="btn btn-success">Sauvegarder</button>
        <a href="admin.php" class="btn btn-secondary">Annuler</a>
    </form>
</div>
</body>
</html>
