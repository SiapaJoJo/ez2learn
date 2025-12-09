<?php
$materials = [
    [
        'id'       => 1,
        'title'    => 'Chapter 1: Marriage (Nikah) in Islam',
        'type'     => 'pdf',
        'dateText' => '15 Nov, 9:30 PM',
        'link'     => 'https://example.com/nikah-chapter1.pdf',
    ],
    [
        'id'       => 2,
        'title'    => 'Quiz 2',
        'type'     => 'quiz',
        'dateText' => '30 min, 10:30 PM',
        'link'     => 'quiz2.php', // link to your quiz page
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ez2Learn - Topic Materials</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 30px 15px;
        }

        .page-container {
            background: #ffffff;
            width: 100%;
            max-width: 1000px;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* HEADER (same style) */
        .ez-header {
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            color: white;
            padding: 18px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
        }
        .ez-brand { display: flex; align-items: center; gap: 15px; }
        .ez-logo-circle {
            width: 60px; height: 60px; border-radius: 50%;
            background: white; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        .ez-logo {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #3198F8 0%, #1e6bb8 100%);
            border-radius: 12px; position: relative;
        }
        .ez-logo::before {
            content: '';
            position: absolute;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            width: 26px;
