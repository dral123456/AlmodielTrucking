<?php

$conn = mysqli_connect(
    "localhost",
    "root",
    "",
    "almodieltrucking"
);

if(!$conn){
    die("Connection failed: " . mysqli_connect_error());
}
?>