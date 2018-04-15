<?php
    $jsonInfoPath = "./data.json";
    $file_db;
    init();

    function init(){
        createFileJSON();
        
        if(FacturaSaved()){
            $clienteName = $_POST['clienteName'];
            $date = $_POST['date'];
            insertNuevaFactura($clienteName,$date);
        }
        else if(FacturaUpdated()){
            $clienteName = $_POST['clienteName'];
            $date = $_POST['date'];
            $facturaID = $_POST['facturaID'];
            updatedateAndClienteFromFactura($date,$clienteName,$facturaID);
        }
        else  if(FacturaDeleted()){
            $facturaID = $_POST['removeFactura'];
            deleteFactura($facturaID); 
        }
        else if(ProductDeleted()){
            $producto_id = $_POST['producto_id'];
            $result = deleteProducto($producto_id); 
        }
    }

    function createFileJSON(){
        if(!FileExists()){
            global $jsonInfoPath;
            $file = fopen($jsonInfoPath, "w+");
            $data = array();
            $data['facturas'] = array();
            $data['productos'] = array();
            $json = json_encode($data);
            fwrite($file, $json);
            fclose($file);
        }
    }

    function FileExists(){
        global $jsonInfoPath;
        return file_exists($jsonInfoPath);
    }

    function FacturaSaved(){
       return isset($_POST['date']) && isset($_POST['clienteName']) && isset($_POST['saveFactura']) && !isset($_POST['facturaID']);
    }

    function updateFactura($facturaID,$subtotal){
        global $jsonInfoPath;
        $facturaResult = getFactura($facturaID);
        
        $facturaID = (int)$facturaResult['facturaID'];
        $tax = $facturaResult['tax'];
        $total = $facturaResult['total'];
        $subtax = ($subtotal * 0.13);
        $tax = $tax + $subtax;
        $total = $total + ($subtotal + $subtax);
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
    
        foreach($json['facturas'] as &$row){
            if($row['facturaID'] == $facturaID){
                $row['tax'] = $tax;
                $row['total'] = $total;
            }
        }
        $dataUpdated = json_encode($json);
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }

   
    function FacturaOpened(){
        return isset($_POST['openFactura']) || isset($_POST['saveProduct']);
    }
    function FacturaDeleted(){
        return isset($_POST['removeFactura']);
    }
    
    function insertNewProduct(){
        global $jsonInfoPath;
        $facturaID = $_POST['facturaID'];
        $cantidad = $_POST['cantidad'];
        $valorUnitario = $_POST['valorUnitario'];
        $descripcion = $_POST['descripcion'];

        $subtotal = $valorUnitario * $cantidad;
        $last_id = getLastID() + 1;
        $stringData = file_get_contents($jsonInfoPath);
        $data = json_decode($stringData, true);
        array_push($data['productos'], 
                    array("producto_id"=>$last_id,"facturaID"=>$facturaID,"cantidad"=>$cantidad,"descripcion"=>$descripcion,
                         "valorUnitario"=>$valorUnitario,"subtotal"=>$subtotal));
        $json = json_encode($data);
        $file = fopen($jsonInfoPath, "w+");

        fwrite($file, $json);
        fclose($file);
        updateFactura($facturaID,$subtotal);
    }


    function FacturaUpdated(){
        return isset($_POST['date']) && isset($_POST['clienteName']) && isset($_POST['saveFactura']) && isset($_POST['facturaID']);
    }
    function productSaved(){
        return isset($_POST['cantidad']) && isset($_POST['descripcion']) && isset($_POST['valorUnitario']) && isset($_POST['saveProduct']) && isset($_POST['facturaID']);
    }

    function getLastID(){
        global $jsonInfoPath;
        $last_id = 0;
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(empty($json['productos'])){
            return $last_id;
        }
        else{
            foreach($json['productos'] as $row){
                $last_id = (int)$row['producto_id'];
            }
            return $last_id;
        }
    }

    function deleteFactureProducts($facturaID){
        global $jsonInfoPath;
        
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            $i=0;
            foreach($json['productos'] as $row) {
                if($row['facturaID'] == $facturaID){
                    unset($json['productos'][$i]);
                }
                $i++;
            }
        }
        $json['productos'] = array_values($json['productos']);
        $dataUpdated = json_encode($json);
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }
    


    function insertNuevaFactura($clienteName, $date){
        global $jsonInfoPath;
        $tax = 0.00;
        $total = 0.00;
        $last_id = getLastFacturaIdInserted() + 1;
        
        $stringData = file_get_contents($jsonInfoPath);
        $data = json_decode($stringData, true);
        
        array_push($data['facturas'], array("facturaID"=>$last_id,"clienteName"=>$clienteName,"date"=>$date,"tax"=>$tax,"total"=>$total));
        $json = json_encode($data);
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $json);
        fclose($file);
    }

    function ProductDeleted(){
        return isset($_POST['remover-producto']);
    }





    function updatedateAndClienteFromFactura($newdate,$newclienteName,$facturaID){
        global $jsonInfoPath;
        $facturaResult = getFactura($facturaID);
        
        $facturaID = (int)$facturaResult['facturaID'];
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
    
        foreach($json['facturas'] as &$row){
            if($row['facturaID'] == $facturaID){
                $row['date'] = $newdate;
                $row['clienteName'] = $newclienteName;
            }
        }
        $dataUpdated = json_encode($json);
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }


    function getFactura($facturaID){
        global $jsonInfoPath;
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            foreach($json['facturas'] as $row) {
                if($row['facturaID'] == $facturaID){
                    return $row;
                }
            }
            return array();
        }
    }

    function getTableInfo(){
        global $jsonInfoPath;
        
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            foreach($json['facturas'] as $row) {
                $facturaID = $row['facturaID'];
                $clienteName = $row['clienteName'];
                echo "<tr class='odd'>";
                echo "<td>$facturaID</td>";
                echo "<td>$clienteName</td>";
                echo "<td><form method='POST' action='index.php'><input type='hidden' name='removeFactura' value='$facturaID'><input type='submit' value='Eliminar' class='btn btn-danger'></form>";
                echo "<form method='POST' action='index.php'><input type='hidden' name='openFactura' value='$facturaID'><input type='submit' value='Abrir' class='btn btn-info'></form></td>";
                echo "</tr>";
            }
        } 
    }

   


  
    function getProductById($producto_id){
        global $jsonInfoPath;
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            foreach($json['productos'] as $row) {
                if($row['producto_id'] == $producto_id){
                    return $row;
                }
            }
            return array();
        }
    }

    function getLastFacturaIdInserted(){
        global $jsonInfoPath;
        $last_id = 0;
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(empty($json['facturas'])){
            return $last_id;
        }
        else{
            foreach($json['facturas'] as $row){
                $last_id = (int)$row['facturaID'];
            }
            return $last_id;
        }
    }

    function getFactureProducts($facturaID){
        global $jsonInfoPath;
        $products = array();
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            foreach($json['productos'] as $row) {
                if($row['facturaID'] == $facturaID){
                    array_push($products,$row);
                }
            }
            return $products;
        }
    }

    


    function getFacturaModule(){
        if(FacturaOpened()){
            printCaseOpen();
        }
        else{
            printNormalCase();
        }
        
    }

    function printNormalCase(){
        echo "<form method='POST' action='index.php'>";
        echo "<table class='facturaTableForm'>";

        echo "<tr >";
        echo "<th>";
        echo "<label for='clienteName'>Nombre Cliente</label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='text' id='clienteName' name='clienteName' >";
        echo "</th>";
        echo "</tr>";

        echo "<tr >";
        echo "<th>";
        echo "<label for='facturaID'>ID Factura</label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='text' id='facturaID' name='facturaID'  disabled>";
        echo "</th>";
        echo "</tr>";

        echo "<tr >";
        echo "<th>";
        echo "<label for='date'>Fecha</label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='datetime-local' id='date' name='date' >";
        echo "</th>";
        echo "</tr>";

        echo "<tr >";
        echo "<th>";
        echo "<label for='tax'>Impuesto</label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='number' id='tax' name='tax' step='0.01' value='0.00'  disabled>";
        echo "</th>";
        echo "</tr>";

        echo "<tr >";
        echo "<th>";
        echo "<label for='total'>Total</label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='number' id='total' name='total' step='0.01' value='0.00'  disabled>";
        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        echo "<input type='submit' name='saveFactura' value='Guardar' >";
        echo "</th>";
        echo "</tr>";
        echo "</table>";
        echo "</form>";
    }

    function printCaseOpen(){
        if(isset($_POST['openFactura'])){
            $facturaID = $_POST['openFactura'];
        }
        else if(productSaved()){
            $facturaID = $_POST['facturaID'];
            insertNewProduct();
        }
        
        $result = getFactura($facturaID);
        $facturaID = $result['facturaID'];
        $clienteName = $result['clienteName'];
        $date = $result['date'];
        $tax = $result['tax'];
        $total = $result['total'];
        echo "<form method='POST' action='index.php'>";
        echo "<table class='facturaTableForm'>";


        echo "<tr>";
        echo "<th>";
        echo "<label for='clienteName'>Nombre Cliente </label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='text' id='clienteName' name='clienteName' value='$clienteName' >";
        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        echo "<label for='facturaID'>ID Factura</label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='text' id='facturaID' name='facturaID' value='$facturaID'  disabled>";
        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        echo "<label for='date'>Fecha</label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='datetime-local' id='date' name='date' value='$date' >";
        echo "</th>";
        echo "</tr>";

        echo "<tr>";
     
        echo "<table class='table'>";
        echo "<thead>";
        echo "<tr>";
        echo "<th scope='col'>Cantidad</th>
        <th scope='col'>Descripcion</th><th scope='col'>Valor Unitario</th><th scope='col'>Subtotal</th><th scope='col'>Action</th>";
        echo "</th>";
        echo "</tr></thead>";
        echo "<tbody>";
        $productsresult = getFactureProducts($facturaID);
        if(!empty($productsresult)){
            foreach($productsresult as $row){
                $producto_id = $row['producto_id'];
                $cantidad = $row['cantidad'];
                $descripcion = $row['descripcion'];
                $valorUnitario = $row['valorUnitario'];
                $subtotal = $row['subtotal'];
                echo "<input type='hidden' name='producto_id' value='$producto_id'>";
                echo "<tr><td>$cantidad</td><td>$descripcion</td><td>$valorUnitario</td><td>$subtotal</td>";
                echo "<td><input type='submit' name='remover-producto' value='Remover'></td></tr>";
            }
        }
        echo "<tr>";
        echo "<th>";
        echo "<input type='hidden' name='facturaID' value='$facturaID'>";
        echo "<td><input type='number' id='cantidad' name='cantidad'></td>";
        echo "<td><input type='text' id='descripcion' name='descripcion'></td>";
        echo "<td><input type='number' id='valorUnitario' name='valorUnitario' step='0.01'></td>";
        echo "<td><input type='number' id='subtotal' name='subtotal' step='0.01' disabled></td>";
        echo "<td><input type='submit' name='saveProduct' value='Guardar Producto' ></td>";
        echo "</th>";
        echo "</tr>";

        echo "</tbody>";
        echo "</table>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        echo "<label for='tax'> Impuesto </label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='number' id='tax' name='tax' step='0.01' value='$tax'  disabled>";
        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        echo "<label for='total'>Total</label>";
        echo "</th>";
        echo "<th>";
        echo "<input type='number' id='total' name='total' step='0.01' value='$total'  disabled>";
        echo "</th>";
        echo "</tr>";

        echo "<tr>";
        echo "<th>";
        echo "<input type='submit' name='saveFactura' value='Guardar' >";
        echo "</th>";
        echo "</tr>";
        echo "</table>";
        echo "</form>";
    }

    function deleteProducto($producto_id){
        global $jsonInfoPath;
        
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['productos'])){
            $i=0;
            foreach($json['productos'] as $row) {
                if($row['producto_id'] == $producto_id){
                    $subtotal = $row['subtotal'] * -1;
                    $facturaID = $row['facturaID'];
                    unset($json['productos'][$i]);
                }
                $i++;
            }
        }
        $json['productos'] = array_values($json['productos']);
        $dataUpdated = json_encode($json);
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
        updateFactura($facturaID,$subtotal);
    }

    function deleteFactura($facturaID){
        global $jsonInfoPath;
        deleteFactureProducts($facturaID);
        $data = file_get_contents($jsonInfoPath);
        $json = json_decode($data, true);
        if(!empty($json['facturas'])){
            $i=0;
            foreach($json['facturas'] as $row) {
                if($row['facturaID'] == $facturaID){
                    unset($json['facturas'][$i]);
                }
                $i++;
            }
        }
        $json['facturas'] = array_values($json['facturas']);
        $dataUpdated = json_encode($json);
        $file = fopen($jsonInfoPath, "w+");
        fwrite($file, $dataUpdated);
        fclose($file);
    }

?>
<!DOCTYPE html>
<html>
    <head>   
        <meta charset="utf-8">
        <title>Tarea 5</title>
        <link rel="stylesheet" href="style.css">
    </head>
    <body>
    <header class="main-title">
            <h1>Tarea Facturas JSON</h1>
        </header>
        <main  id="main-index">
            <div class="content">
                <div class="FacturasTableDiv">
                    <p>Facturas</p>
                    <div class="">
                        <table class="FacturasTable">
                            <thead class="tableH">
                                <tr>
                                    <th>ID</th>
                                    <th>Cliente</th>
                                    <th>Opciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    getTableInfo();
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div  class="FacturaFormDiv">
                <p>Factura</p>
                    <?php 
                        getFacturaModule();
                    ?>
                </div>
            </div>
        </main>
        <footer></footer>
    </body>
</html>