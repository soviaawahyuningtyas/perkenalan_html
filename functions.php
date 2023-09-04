<?php

require_once "./vendor/autoload.php";

use Gumlet\ImageResize;

// Koneksi ke database
$conn = new mysqli('sofia.nganjukkab.go.id', 'sofia', 'sofia2023', 'sofia');
// $conn = new mysqli("localhost", "root", "", "sofia");

function query($query)
{
    global $conn;
    $result = mysqli_query($conn, $query);
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}

function tambah($data)
{
    // Ambil data dari tiap elemen dalam form
    global $conn;
    $nama = htmlspecialchars($data["nama"]);
    $asal_sekolah = htmlspecialchars($data["asal_sekolah"]);
    $jurusan = htmlspecialchars($data["jurusan"]);
    $phone = htmlspecialchars($data["phone"]);
    // $gambar = htmlspecialchars($data["gambar"]);

    // Upload gambar dahulu
    $gambar = upload();
    if (!$gambar) {
        return false;
    }

    // Query insert data
    $query2 = "INSERT INTO daftar (nama, asal_sekolah, jurusan, phone, gambar)
		VALUES
		('$nama','$asal_sekolah','$jurusan','$phone', '$gambar')
		";
    return $conn->query($query2);
}

function hapus($id)
{
    global $conn;
    $file = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT * FROM daftar WHERE id = '$id'")
    );
    unlink("imgmariadb/" . $file["gambar"]);
    $hapus = "DELETE FROM daftar WHERE id = '$id'";
    mysqli_query($conn, $hapus);
    return mysqli_affected_rows($conn);
}

function upload()
{
    $namaFile = $_FILES["gambar"]["name"];
    $ukuranFile = $_FILES["gambar"]["size"];
    $error = $_FILES["gambar"]["error"];
    $tmpName = $_FILES["gambar"]["tmp_name"];

    // Cek apakah tidak ada gambar yang diupload
    if ($error === 4) {
        echo "<script>
            alert('Pilih Gambar Terlebih Dahulu!');
            </script>";
        return false;
    }

    // Cek apakah yang diupload adalah gambar
    $ekstensiGambarValid = ["jpg", "jpeg", "gif", "png"];
    $ekstensiGambar = explode(".", $namaFile); //Delimiter = '.'
    // sovia.jpg = ['sovia', 'jpg']
    // $ekstensiGambar = $ekstensiGambar[1];
    // ['sovia', 'jpg'];
    $ekstensiGambar = strtolower(end($ekstensiGambar)); //strlower = format die diubah ke huruf kecil

    if (!in_array($ekstensiGambar, $ekstensiGambarValid)) {
        //(needle, haystack), needle=jarum; haystack=jerami. diibaratkan mencari jarum di dalam jerami
        echo "<script>
            alert('Yang Anda Upload Bukan Gambar!');
          </script>";
        return false;
    }

    // Cek jika ukurannya terlalu besar
    if ($ukuranFile > 1000000) {
        //1.000.000 Byte = 1 MegaByte
        echo "<script>
              alert('Ukuran Gambar Terlalu Besar!');
            </script>";
        return false;
    }

    // Lolos pengecekan, gambar siap diupload
    // Generate nama gambar baru untuk gambar jika nama file sama
    $namaFileBaru = uniqid(); //uniqid untuk membangkitkan string random
    $namaFileBaru .= ".";
    $namaFileBaru .= $ekstensiGambar;
    // var_dump($namaFileBaru); die;

    //Dari ini kompress image

    // tentukan path gambar
    $gambar_lok = __DIR__ . "/imgmariadb/";
    $gambar = $gambar_lok . $namaFileBaru;

    move_uploaded_file($tmpName, "imgmariadb/" . $namaFileBaru);
    // tentukan kualitas gambar yang diinginkan (antara 0 dan 100)
    $kualitas = 50;
    $image = new ImageResize($gambar);
    $image->quality_jpg = $kualitas;
    $image->save($gambar);
    return $namaFileBaru;
}

function ubah($data)
{
    global $conn;
    $id           = $data["id"];
    $nama         = htmlspecialchars($data["nama"]);
    $asal_sekolah = htmlspecialchars($data["asal_sekolah"]);
    $jurusan      = htmlspecialchars($data["jurusan"]);
    $phone        = htmlspecialchars($data["phone"]);
    $gambarLama   = htmlspecialchars($data["gambarLama"]);

    // cek apakah user pilih gambar baru atau tidak
    if ($_FILES["gambar"]["error"] === 4) {
        $gambar = $gambarLama;
    } else {
        $gambar = upload();
    }

    $query = "UPDATE daftar SET
    nama ='$nama',
    asal_sekolah ='$asal_sekolah',
    jurusan ='$jurusan',
    phone ='$phone',
    gambar = '$gambar'
    WHERE id = $id
    ";

        // Cek ERROR
    if ($conn->query($query) === true) {
        echo 'Record updated successfully';
    } else {
        echo 'Error updating record: ' . $conn->error;
    }

    return mysqli_affected_rows($conn);
}
