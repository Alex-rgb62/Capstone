<?php

session_start();

require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../auth/login.php");
    exit;
}

$user_id = $_SESSION["user_id"];

$stmt = $pdo->prepare("
    SELECT id, first_name, last_name, email, role, phone, address, status
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$user_id]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: ../auth/login.php");
    exit;
}

if ($user["role"] !== "resident") {
    header("Location: ../index.php");
    exit;
}

$full_name = $user["first_name"] . " " . $user["last_name"];

$initials =
    strtoupper(substr($user["first_name"], 0, 1)) .
    strtoupper(substr($user["last_name"], 0, 1));


// Get the resident's recent requests
$request_stmt = $pdo->prepare("
    SELECT
        id,
        reference_no,
        service,
        status,
        estimated_date,
        submitted_at
    FROM requests
    WHERE user_id = ?
    ORDER BY submitted_at DESC
    LIMIT 5
");

$request_stmt->execute([$user_id]);

$requests = $request_stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Municipality of Tanza — Resident Portal</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap');

  :root{
    --ink:#10233F;
    --bay:#1B4E7E;
    --bay-deep:#123A5F;
    --bay-light:#EAF1F8;
    --gold:#E3A23C;
    --gold-deep:#C6822A;
    --paper:#FAF7F1;
    --white:#FFFFFF;
    --slate:#3A3F47;
    --muted:#767C87;
    --success:#3F8F5F;
    --success-bg:#E8F3EC;
    --alert:#C1432E;
    --alert-bg:#FBEAE6;
    --line:#E9E4D8;
    --line-soft:#F0ECE1;
    --radius:14px;
    --sidebar-w:248px;
  }
  *{box-sizing:border-box;}
  html,body{margin:0;padding:0;}
  body{
    background:var(--paper);
    color:var(--slate);
    font-family:'Inter',sans-serif;
    -webkit-font-smoothing:antialiased;
  }
  h1,h2,h3,.display{font-family:'Fraunces',serif;}
  .mono{font-family:'IBM Plex Mono',monospace;}
  button{font-family:inherit;}

  /* ---------- Municipal Announcements hero carousel ---------- */
  .announce-section{margin-bottom:22px;}
  .announce-section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
  .announce-section-head h2{font-size:16px;margin:0;color:var(--ink);font-weight:600;}
  .announce-section-head .sub{font-size:12px;color:var(--muted);margin-top:3px;}

  .hero-carousel{
    position:relative;
    width:100%;
    height:360px;
    border-radius:var(--radius);
    overflow:hidden;
    background:var(--ink);
    box-shadow:0 18px 40px -22px rgba(16,35,63,.35);
  }
  .hero-track{
    display:flex;
    height:100%;
    transition:transform .6s cubic-bezier(.65,0,.35,1);
    will-change:transform;
  }
  .hero-slide{
    position:relative;
    min-width:100%;
    height:100%;
    background-size:cover;
    background-position:center;
  }
  .hero-slide::after{
    content:"";
    position:absolute;inset:0;
    background:linear-gradient(180deg, rgba(16,35,63,.15) 0%, rgba(16,35,63,.55) 60%, rgba(16,35,63,.88) 100%);
  }
  .hero-content{
    position:absolute;left:0;right:0;bottom:0;
    padding:34px 44px 38px;
    max-width:600px;
    z-index:2;
    color:var(--white);
  }
  .hero-official-badge{
    display:inline-flex;align-items:center;gap:7px;
    font-family:'Inter',sans-serif;font-size:11.5px;font-weight:700;letter-spacing:.03em;
    color:var(--ink);background:var(--gold);
    padding:5px 12px;border-radius:20px;margin-bottom:14px;
    box-shadow:0 6px 16px -6px rgba(227,162,60,.6);
  }
  .hero-eyebrow{
    display:inline-flex;align-items:center;gap:9px;
    font-family:'IBM Plex Mono',monospace;font-size:11px;letter-spacing:.1em;text-transform:uppercase;
    color:var(--gold);margin-bottom:12px;
  }
  .hero-eyebrow .hero-cat{
    background:rgba(255,255,255,.14);border:1px solid rgba(255,255,255,.3);
    padding:3px 9px;border-radius:6px;color:var(--white);
  }
  .hero-eyebrow .hero-date-sep{color:rgba(255,255,255,.4);}
  .hero-eyebrow .hero-date{color:#E7ECF3;letter-spacing:.04em;}
  .hero-heading{
    font-family:'Fraunces',serif;font-weight:600;font-size:30px;line-height:1.15;
    margin:0 0 12px;color:var(--white);
  }
  .hero-desc{
    font-size:14px;line-height:1.6;color:#E7ECF3;margin:0 0 20px;
  }
  .hero-cta{
    display:inline-flex;align-items:center;gap:9px;
    background:var(--gold);color:var(--ink);border:none;
    font-family:'Inter',sans-serif;font-weight:600;font-size:14px;
    padding:12px 22px;border-radius:10px;cursor:pointer;
    text-decoration:none;transition:background .15s,transform .15s;
  }
  .hero-cta:hover{background:var(--gold-deep);transform:translateY(-1px);}

  .hero-arrow{
    position:absolute;top:50%;transform:translateY(-50%);
    width:42px;height:42px;border-radius:50%;
    background:rgba(16,35,63,.45);border:1px solid rgba(255,255,255,.35);
    color:var(--white);font-size:18px;line-height:1;
    display:flex;align-items:center;justify-content:center;
    cursor:pointer;z-index:3;transition:background .15s;
  }
  .hero-arrow:hover{background:rgba(16,35,63,.75);}
  .hero-arrow.prev{left:20px;}
  .hero-arrow.next{right:20px;}

  .hero-dots{
    position:absolute;right:44px;bottom:38px;z-index:3;
    display:flex;gap:9px;
  }
  .hero-dot{
    width:9px;height:9px;border-radius:50%;
    background:rgba(255,255,255,.4);border:none;cursor:pointer;padding:0;
    transition:background .15s,transform .15s;
  }
  .hero-dot.active{background:var(--gold);transform:scale(1.2);}

  .hero-section .announce-section-head h2{font-size:18px;}

  @media(max-width:760px){
    .hero-carousel{height:340px;}
    .hero-content{padding:24px 20px 28px;max-width:100%;}
    .hero-heading{font-size:22px;}
    .hero-desc{font-size:13px;}
    .hero-dots{right:20px;bottom:22px;}
    .hero-arrow{width:36px;height:36px;font-size:16px;}
    .hero-arrow.prev{left:10px;}
    .hero-arrow.next{right:10px;}
    .hero-official-badge{font-size:10.5px;padding:4px 10px;}
  }
  @media(max-width:480px){
    .hero-carousel{height:300px;}
    .hero-heading{font-size:19px;}
    .hero-cta{padding:10px 18px;font-size:13px;}
  }

  /* ================= APP SHELL ================= */
  .app-shell{display:flex;min-height:100vh;}

  /* ---------- Sidebar ---------- */
  .sidebar{
    width:var(--sidebar-w);flex-shrink:0;
    background:linear-gradient(180deg, var(--ink) 0%, var(--bay-deep) 100%);
    color:var(--white);
    display:flex;flex-direction:column;
    position:sticky;top:0;height:100vh;overflow-y:auto;
    z-index:60;
  }
  .sidebar-brand{
    display:flex;align-items:center;gap:11px;
    padding:20px 18px 16px;
    border-bottom:1px solid rgba(255,255,255,.1);
  }
  .seal{
    width:34px;height:34px;border-radius:50%;
    background:radial-gradient(circle at 35% 30%, var(--gold) 0%, var(--gold-deep) 55%, #8f5f1a 100%);
    border:2px solid rgba(255,255,255,.5);
    display:flex;align-items:center;justify-content:center;
    font-family:'Fraunces',serif;font-weight:700;color:var(--ink);font-size:13px;
    flex-shrink:0;
  }
  .sidebar-brand .name{font-family:'Fraunces',serif;font-size:15px;font-weight:600;line-height:1.2;}
  .sidebar-brand .eyebrow{font-size:10px;color:#AEC0D6;margin-top:1px;}

  .sidebar-user{
    display:flex;align-items:center;gap:10px;
    padding:14px 18px;
    border-bottom:1px solid rgba(255,255,255,.08);
  }
  .avatar{
    width:32px;height:32px;border-radius:50%;
    background:rgba(255,255,255,.14);color:var(--white);
    display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;
    font-family:'Fraunces',serif;flex-shrink:0;
  }
  .avatar.sm{width:28px;height:28px;font-size:11px;background:var(--bay-light);color:var(--ink);}
  .user-info{min-width:0;}
  .user-info .user-name{font-size:13px;font-weight:600;color:var(--white);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
  .user-info .user-status{
    font-size:10.5px;color:#9FD8B4;display:flex;align-items:center;gap:5px;margin-top:2px;
  }
  .user-info .user-status .dot{width:5px;height:5px;border-radius:50%;background:#5FCB84;flex-shrink:0;}

  .sidebar-label{
    font-size:10px;letter-spacing:.1em;text-transform:uppercase;color:#7F91A9;
    padding:16px 18px 6px;font-weight:600;
  }
  .sidebar-nav{display:flex;flex-direction:column;padding:0 10px;gap:1px;}
  .nav-item{
    display:flex;align-items:center;gap:11px;
    background:none;border:none;color:#C7D2E1;text-align:left;
    font-size:13.5px;font-weight:500;
    padding:9px 12px;border-radius:9px;cursor:pointer;
    transition:background .15s,color .15s;
    position:relative;
  }
  .nav-item .ic{font-size:15px;width:18px;text-align:center;flex-shrink:0;}
  .nav-item:hover{background:rgba(255,255,255,.07);color:var(--white);}
  .nav-item.active{background:rgba(255,255,255,.13);color:var(--white);font-weight:600;}
  .nav-badge{
    margin-left:auto;background:var(--gold);color:var(--ink);
    font-size:10px;font-weight:700;min-width:16px;height:16px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;padding:0 4px;
    font-family:'IBM Plex Mono',monospace;
  }

  .sidebar-footer{margin-top:auto;padding:14px 10px 18px;border-top:1px solid rgba(255,255,255,.08);}
  .nav-item.logout{color:#C7D2E1;}
  .nav-item.logout:hover{background:rgba(193,67,46,.18);color:#F3B7A9;}

  .menu-toggle{display:none;}

  /* ---------- Main column ---------- */
  .main-wrap{flex:1;min-width:0;display:flex;flex-direction:column;}

  .topbar-lite{
    display:flex;align-items:center;gap:16px;
    padding:14px 28px;
    background:var(--white);border-bottom:1px solid var(--line);
    position:sticky;top:0;z-index:50;
  }
  .search-box{flex:1;max-width:420px;}
  .search-box input{
    width:100%;border:1px solid var(--line);border-radius:10px;
    padding:9px 14px;font-size:13.5px;font-family:'Inter',sans-serif;color:var(--slate);
    background:var(--paper);
  }
  .search-box input:focus{outline:none;border-color:var(--bay);background:var(--white);}
  .topbar-right{display:flex;align-items:center;gap:14px;margin-left:auto;}
  .bell-btn{
    position:relative;background:none;border:1px solid var(--line);
    color:var(--ink);width:36px;height:36px;border-radius:10px;cursor:pointer;
    display:flex;align-items:center;justify-content:center;font-size:15px;
    transition:background .15s,border-color .15s;
  }
  .bell-btn:hover{background:var(--bay-light);border-color:var(--bay);}
  .bell-dot{
    position:absolute;top:-5px;right:-5px;background:var(--alert);color:var(--white);
    font-size:9.5px;font-weight:700;min-width:16px;height:16px;border-radius:8px;
    display:flex;align-items:center;justify-content:center;padding:0 3px;font-family:'IBM Plex Mono',monospace;
  }
  .profile-menu{position:relative;}
  .profile-trigger{
    display:flex;align-items:center;gap:8px;background:none;border:none;cursor:pointer;
    padding:5px 8px 5px 5px;border-radius:10px;transition:background .15s;
  }
  .profile-trigger:hover{background:var(--bay-light);}
  .profile-trigger span{font-size:13px;font-weight:600;color:var(--ink);}
  .profile-trigger .caret{font-size:10px;color:var(--muted);}
  .profile-dropdown{
    position:absolute;top:calc(100% + 8px);right:0;min-width:200px;
    background:var(--white);border:1px solid var(--line);border-radius:12px;
    box-shadow:0 14px 32px -14px rgba(16,35,63,.28);
    padding:6px;display:none;flex-direction:column;gap:1px;z-index:70;
  }
  .profile-dropdown.open{display:flex;}
  .profile-dropdown button{
    display:flex;align-items:center;gap:9px;
    background:none;border:none;text-align:left;font-size:13px;color:var(--slate);
    padding:9px 10px;border-radius:8px;cursor:pointer;transition:background .15s;
  }
  .profile-dropdown button:hover{background:var(--bay-light);color:var(--bay-deep);}
  .profile-dropdown .divider{height:1px;background:var(--line);margin:4px 2px;}
  .profile-dropdown button.logout-item:hover{background:var(--alert-bg);color:var(--alert);}

  /* ================= DASHBOARD ================= */
  #dashboard{display:block;padding:24px 28px 60px;max-width:1220px;width:100%;margin:0 auto;}

  .welcome-banner{
    display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;
    background:linear-gradient(120deg, var(--bay) 0%, var(--ink) 100%);
    border-radius:var(--radius);
    padding:24px 30px;
    margin-bottom:22px;
    color:var(--white);
    box-shadow:0 16px 34px -22px rgba(16,35,63,.45);
  }
  .welcome-banner .greet{margin:0;font-size:13px;color:#CBDCEE;}
  .welcome-banner h1{margin:2px 0 6px;font-size:24px;font-weight:600;color:var(--white);}
  .welcome-banner .sub{margin:0 0 10px;font-size:13px;color:#DCE6F1;}
  .verify-badge{
    display:inline-flex;align-items:center;gap:6px;
    background:rgba(95,203,132,.18);border:1px solid rgba(95,203,132,.4);color:#8FE3AC;
    font-size:11.5px;font-weight:600;padding:4px 11px;border-radius:20px;
  }
  .verify-badge .dot{width:6px;height:6px;border-radius:50%;background:#5FCB84;}
  .welcome-actions{display:flex;gap:10px;flex-shrink:0;}
  .btn-ghost,.btn-solid{
    display:inline-flex;align-items:center;gap:7px;
    font-size:13.5px;font-weight:600;border-radius:10px;padding:10px 18px;
    cursor:pointer;text-decoration:none;border:none;transition:transform .15s,background .15s;
  }
  .btn-ghost{background:rgba(255,255,255,.14);color:var(--white);border:1px solid rgba(255,255,255,.3);}
  .btn-ghost:hover{background:rgba(255,255,255,.22);}
  .btn-solid{background:var(--gold);color:var(--ink);}
  .btn-solid:hover{background:var(--gold-deep);transform:translateY(-1px);}

  .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:22px;}
  .stat-card{
    background:var(--white);border:1px solid var(--line-soft);border-radius:12px;
    padding:15px 17px;display:flex;align-items:flex-start;justify-content:space-between;gap:10px;
  }
  .stat-card .lbl{font-size:11.5px;color:var(--muted);margin-bottom:8px;}
  .stat-card .num{font-family:'Fraunces',serif;font-size:26px;font-weight:600;color:var(--ink);line-height:1;}
  .stat-icon{
    width:34px;height:34px;border-radius:9px;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;font-size:15px;
  }
  .stat-icon.blue{background:var(--bay-light);color:var(--bay);}
  .stat-icon.green{background:var(--success-bg);color:var(--success);}
  .stat-icon.red{background:var(--alert-bg);color:var(--alert);}
  .stat-icon.purple{background:#EFE8F7;color:#7B4FA3;}

  .content-grid{display:grid;grid-template-columns:1.6fr 1fr;gap:18px;align-items:start;}
  .col-main,.col-side{display:flex;flex-direction:column;gap:18px;min-width:0;}

  .panel{border-radius:var(--radius);}
  .panel-primary{
    background:var(--white);border:1px solid var(--line);border-radius:var(--radius);
    padding:20px 22px 8px;
    box-shadow:0 12px 28px -20px rgba(16,35,63,.18);
  }
  .panel-light{
    background:var(--white);border:1px solid var(--line-soft);border-radius:var(--radius);
    padding:18px 20px;
  }
  .panel-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;}
  .panel-head h2{font-size:15.5px;margin:0;color:var(--ink);font-weight:600;display:flex;align-items:center;gap:8px;}
  .panel-head a{font-size:12px;color:var(--bay);text-decoration:none;font-weight:600;white-space:nowrap;}
  .count-badge{
    background:var(--alert);color:var(--white);font-size:10.5px;font-weight:700;
    min-width:18px;height:18px;border-radius:9px;display:inline-flex;align-items:center;justify-content:center;
    padding:0 5px;font-family:'IBM Plex Mono',monospace;
  }

  /* My Requests table */
  .req-table{width:100%;border-collapse:collapse;}
  .req-table thead th{
    text-align:left;font-size:10.5px;letter-spacing:.04em;text-transform:uppercase;color:var(--muted);
    font-weight:600;padding:0 8px 10px;border-bottom:1px solid var(--line);
  }
  .req-table tbody td{
    padding:13px 8px;border-bottom:1px solid var(--line-soft);font-size:13px;vertical-align:middle;
  }
  .req-table tbody tr:last-child td{border-bottom:none;}
  .req-ref{font-family:'IBM Plex Mono',monospace;font-size:11.5px;color:var(--bay);font-weight:600;}
  .req-service{color:var(--ink);font-weight:500;}
  .req-date{color:var(--muted);font-size:12.5px;}
  .req-view{font-size:12px;color:var(--bay);font-weight:600;text-decoration:none;cursor:pointer;background:none;border:none;}
  .status{
    font-size:11px;font-weight:600;padding:4px 10px;border-radius:20px;display:inline-flex;align-items:center;gap:5px;
  }
  .status .dot{width:5px;height:5px;border-radius:50%;}
  .status.progress{background:var(--bay-light);color:var(--bay);}
  .status.progress .dot{background:var(--bay);}
  .status.done{background:var(--success-bg);color:var(--success);}
  .status.done .dot{background:var(--success);}

  /* Quick actions */
  .quick-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:11px;}
  .qa{
    border:1px solid var(--line-soft);border-radius:11px;padding:15px 12px;background:var(--paper);
    cursor:pointer;transition:border-color .15s,background .15s,transform .15s;
    display:flex;flex-direction:column;align-items:flex-start;gap:8px;
  }
  .qa:hover{border-color:var(--bay);background:var(--bay-light);transform:translateY(-1px);}
  .qa .ic{
    width:30px;height:30px;border-radius:8px;background:var(--white);border:1px solid var(--line);
    display:flex;align-items:center;justify-content:center;font-size:13.5px;
  }
  .qa .t{font-size:12.5px;font-weight:600;color:var(--ink);line-height:1.3;}

  /* Notifications */
  .notif-list{display:flex;flex-direction:column;}
  .notif-item{
    display:flex;gap:10px;padding:12px 0;border-top:1px solid var(--line-soft);
  }
  .notif-item:first-child{border-top:none;padding-top:0;}
  .notif-dot{
    width:7px;height:7px;border-radius:50%;background:var(--bay);flex-shrink:0;margin-top:5px;
  }
  .notif-item.read .notif-dot{background:transparent;}
  .notif-body .t{font-size:12.5px;font-weight:600;color:var(--ink);}
  .notif-body .d{font-size:12px;color:var(--muted);margin-top:2px;line-height:1.5;}
  .notif-body .time{font-size:10.5px;color:#A6ACB6;margin-top:5px;font-family:'IBM Plex Mono',monospace;}

  /* Appointments */
  .appt-list{display:flex;flex-direction:column;}
  .appt{display:flex;gap:11px;padding:11px 0;border-top:1px solid var(--line-soft);align-items:center;}
  .appt:first-child{border-top:none;padding-top:0;}
  .appt-date{
    background:var(--bay-light);color:var(--bay);border-radius:9px;width:40px;height:40px;flex-shrink:0;
    display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:'IBM Plex Mono',monospace;
  }
  .appt-date .d{font-size:14px;font-weight:600;line-height:1;}
  .appt-date .m{font-size:8px;text-transform:uppercase;letter-spacing:.04em;}
  .appt-info .t{font-size:12.5px;font-weight:600;color:var(--ink);}
  .appt-info .s{font-size:11.5px;color:var(--muted);margin-top:2px;}

  /* ---------- Responsive ---------- */
  @media(max-width:1020px){
    .content-grid{grid-template-columns:1fr;}
  }
  @media(max-width:760px){
    .sidebar{
      position:fixed;left:0;top:0;transform:translateX(-100%);
      transition:transform .2s ease;box-shadow:0 0 0 rgba(0,0,0,0);
    }
    .sidebar.open{transform:translateX(0);box-shadow:20px 0 40px -20px rgba(16,35,63,.5);}
    .menu-toggle{
      display:flex;align-items:center;justify-content:center;
      width:36px;height:36px;border-radius:9px;border:1px solid var(--line);
      background:var(--white);color:var(--ink);font-size:16px;cursor:pointer;flex-shrink:0;
    }
    .search-box{max-width:none;}
    #dashboard{padding:18px 16px 50px;}
    .stat-grid{grid-template-columns:1fr 1fr;}
    .quick-actions{grid-template-columns:1fr 1fr;}
    .welcome-banner{padding:20px;}
    .req-table thead{display:none;}
    .req-table tbody tr{display:flex;flex-direction:column;gap:4px;padding:12px 0;}
    .req-table tbody td{padding:2px 0;border-bottom:none;}
    .req-table tbody tr{border-bottom:1px solid var(--line-soft);}
  }
  @media(max-width:480px){
    .stat-grid{grid-template-columns:1fr;}
    .quick-actions{grid-template-columns:1fr;}
    .welcome-actions{width:100%;}
    .welcome-actions .btn-ghost,.welcome-actions .btn-solid{flex:1;justify-content:center;}
  }

  .sidebar-overlay{
    display:none;position:fixed;inset:0;background:rgba(16,35,63,.4);z-index:55;
  }
  .sidebar-overlay.open{display:block;}
</style>
</head>
<body>

<div class="app-shell">

  <!-- ============ SIDEBAR ============ -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div class="seal">T</div>
      <div>
        <div class="name">Tanza Portal</div>
        <div class="eyebrow">Municipality of Tanza</div>
      </div>
    </div>

    <div class="sidebar-user">
      <div class="avatar">
        <?= htmlspecialchars($initials) ?>
      </div>
        <div class="user-info">
            <div class="user-name">
                <?= htmlspecialchars($full_name) ?>
            </div>

            <div class="user-status">
                <span class="dot"></span>
                    <?= htmlspecialchars($user["status"]) ?>
            </div>
        </div>
    </div>

    <div class="sidebar-label">Menu</div>
    <nav class="sidebar-nav" id="sidebarNav">
      <button class="nav-item active" data-section="dashboard"><span class="ic">🏠</span>Dashboard</button>
      <button class="nav-item" data-section="services"><span class="ic">🗂</span>Services</button>
      <button class="nav-item" data-section="requests"><span class="ic">📄</span>My Requests</button>
      <button class="nav-item" data-section="appointments"><span class="ic">📅</span>Appointments</button>
      <button class="nav-item" data-section="notifications"><span class="ic">🔔</span>Notifications<span class="nav-badge">3</span></button>
    </nav>

    <div class="sidebar-footer">
      <button class="nav-item logout"><span class="ic">↩</span>Logout</button>
    </div>
  </aside>

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ============ MAIN COLUMN ============ -->
  <div class="main-wrap">
    <header class="topbar-lite">
      <button class="menu-toggle" id="menuToggle" aria-label="Open menu">☰</button>
      <div class="search-box"><input type="text" placeholder="Search services, requests..."></div>
      <div class="topbar-right">
        <button class="bell-btn" aria-label="Notifications">🔔<span class="bell-dot">3</span></button>
        <div class="profile-menu" id="profileMenu">
          <button class="profile-trigger" id="profileTrigger">
            <div class="avatar sm">
                <?= htmlspecialchars($initials) ?>
            </div>
            <span>
                <?= htmlspecialchars($user["first_name"]) ?>
            </span>
            <span class="caret">▾</span>
          </button>
          <div class="profile-dropdown" id="profileDropdown">
            <button>👤 Profile</button>
            <button>🔔 Notification Preferences</button>
            <div class="divider"></div>
            <button class="logout-item">↩ Logout</button>
          </div>
        </div>
      </div>
    </header>

    <main id="dashboard">

      <!-- Welcome banner -->
      <section class="welcome-banner">
        <div class="welcome-left">
          <p class="greet">Good morning,</p>
          <h1><?= htmlspecialchars($full_name) ?>! 👋</h1>
          <p class="sub">Welcome to the Tanza Public Service Portal.</p>
          <span class="verify-badge"><span class="dot"></span>S<?= htmlspecialchars($user["status"]) ?></span>
        </div>
        <div class="welcome-actions">
          <button class="btn-ghost">📄 New Request</button>
          <button class="btn-solid">Track →</button>
        </div>
      </section>

      <!-- Municipal Announcements carousel -->
      <div class="announce-section hero-section">
        <div class="announce-section-head">
          <div>
            <h2>📢 Municipal Announcements</h2>
            <div class="sub">Official notices from the Public Information Office</div>
          </div>
        </div>
        <div class="hero-carousel" id="heroCarousel">
          <div class="hero-track" id="heroTrack"></div>
          <button class="hero-arrow prev" id="heroPrev" aria-label="Previous announcement">‹</button>
          <button class="hero-arrow next" id="heroNext" aria-label="Next announcement">›</button>
          <div class="hero-dots" id="heroDots"></div>
        </div>
      </div>

      <!-- Stats -->
      <section class="stat-grid">
        <div class="stat-card">
          <div><div class="lbl">Active Requests</div><div class="num">1</div></div>
          <div class="stat-icon blue">📄</div>
        </div>
        <div class="stat-card">
          <div><div class="lbl">Completed Requests</div><div class="num">3</div></div>
          <div class="stat-icon green">✓</div>
        </div>
        <div class="stat-card">
          <div><div class="lbl">Unread Notifications</div><div class="num">3</div></div>
          <div class="stat-icon red">🔔</div>
        </div>
        <div class="stat-card">
          <div><div class="lbl">Upcoming Appointments</div><div class="num">2</div></div>
          <div class="stat-icon purple">📅</div>
        </div>
      </section>

      <!-- Main content -->
      <section class="content-grid">

        <div class="col-main">
          <!-- My Requests — primary focus -->
          <div class="panel-primary" id="my-requests">
            <div class="panel-head">
              <h2>My Requests</h2>
              <a href="#">View All →</a>
            </div>
            <table class="req-table">
              <thead>
                <tr><th>Reference No.</th><th>Service</th><th>Status</th><th>Est. Date</th><th></th></tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="req-ref">TZ-2026-00125</span></td>
                  <td><span class="req-service">Business Permit Application</span></td>
                  <td><span class="status progress"><span class="dot"></span>Processing</span></td>
                  <td><span class="req-date">Aug 20, 2026</span></td>
                  <td><button class="req-view">View</button></td>
                </tr>
                <tr>
                  <td><span class="req-ref">TZ-2026-00131</span></td>
                  <td><span class="req-service">Barangay Clearance</span></td>
                  <td><span class="status done"><span class="dot"></span>Completed</span></td>
                  <td><span class="req-date">Aug 10, 2026</span></td>
                  <td><button class="req-view">View</button></td>
                </tr>
                <tr>
                  <td><span class="req-ref">TZ-2026-00098</span></td>
                  <td><span class="req-service">Community Tax Certificate</span></td>
                  <td><span class="status done"><span class="dot"></span>Completed</span></td>
                  <td><span class="req-date">Jul 18, 2026</span></td>
                  <td><button class="req-view">View</button></td>
                </tr>
                <tr>
                  <td><span class="req-ref">TZ-2026-00067</span></td>
                  <td><span class="req-service">Indigency Certificate</span></td>
                  <td><span class="status done"><span class="dot"></span>Completed</span></td>
                  <td><span class="req-date">Jun 23, 2026</span></td>
                  <td><button class="req-view">View</button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Quick Actions -->
          <div class="panel-light">
            <div class="panel-head"><h2>Quick Actions</h2></div>
            <div class="quick-actions">
              <div class="qa"><div class="ic">📝</div><div class="t">Submit New Request</div></div>
              <div class="qa"><div class="ic">📖</div><div class="t">View Citizen's Charter</div></div>
              <div class="qa"><div class="ic">🔎</div><div class="t">Track My Request</div></div>
              <div class="qa"><div class="ic">📅</div><div class="t">My Appointments</div></div>
            </div>
          </div>
        </div>

        <div class="col-side">
          <!-- Notifications -->
          <div class="panel-light" id="notifications">
            <div class="panel-head">
              <h2>Notifications <span class="count-badge">3</span></h2>
              <a href="#">View All</a>
            </div>
            <div class="notif-list">
              <div class="notif-item">
                <div class="notif-dot"></div>
                <div class="notif-body">
                  <div class="t">Request Status Updated</div>
                  <div class="d">Your Business Permit Application (TZ-2026-00125) is currently under review.</div>
                  <div class="time">10 minutes ago</div>
                </div>
              </div>
              <div class="notif-item">
                <div class="notif-dot"></div>
                <div class="notif-body">
                  <div class="t">Estimated Date Updated</div>
                  <div class="d">Your estimated completion date for TZ-2026-00125 has been updated to August 20, 2026.</div>
                  <div class="time">1 hour ago</div>
                </div>
              </div>
              <div class="notif-item">
                <div class="notif-dot"></div>
                <div class="notif-body">
                  <div class="t">Appointment Reminder</div>
                  <div class="d">Your appointment for Business Permit Application is scheduled for August 15, 2026 at 10:00 AM.</div>
                  <div class="time">Yesterday</div>
                </div>
              </div>
              <div class="notif-item read">
                <div class="notif-dot"></div>
                <div class="notif-body">
                  <div class="t">Additional Requirement</div>
                  <div class="d">Please upload a valid government-issued ID to complete your Business Permit application.</div>
                  <div class="time">2 days ago</div>
                </div>
              </div>
            </div>
          </div>

          <!-- Upcoming Appointments -->
          <div class="panel-light" id="appointments">
            <div class="panel-head">
              <h2>Upcoming Appointments</h2>
              <a href="#">View All</a>
            </div>
            <div class="appt-list">
              <div class="appt">
                <div class="appt-date"><span class="d">15</span><span class="m">Aug</span></div>
                <div class="appt-info"><div class="t">Business Permit Consultation</div><div class="s">10:00 AM · Business Permit Office</div></div>
              </div>
              <div class="appt">
                <div class="appt-date"><span class="d">22</span><span class="m">Aug</span></div>
                <div class="appt-info"><div class="t">Document Pickup</div><div class="s">2:00 PM · Municipal Hall</div></div>
              </div>
            </div>
          </div>
        </div>

      </section>
    </main>
  </div>
</div>

<script>
  // Mobile sidebar toggle
  const sidebar = document.getElementById('sidebar');
  const menuToggle = document.getElementById('menuToggle');
  const overlay = document.getElementById('sidebarOverlay');

  function openSidebar(){ sidebar.classList.add('open'); overlay.classList.add('open'); }
  function closeSidebar(){ sidebar.classList.remove('open'); overlay.classList.remove('open'); }

  if(menuToggle){
    menuToggle.addEventListener('click', () => {
      sidebar.classList.contains('open') ? closeSidebar() : openSidebar();
    });
  }
  overlay.addEventListener('click', closeSidebar);

  // Sidebar nav active state (single-page dashboard — sections are anchored below)
  const navItems = document.querySelectorAll('.sidebar-nav .nav-item');
  const sectionMap = {
    dashboard: null, // top of page
    requests: '#my-requests',
    notifications: '#notifications',
    appointments: '#appointments'
  };
  navItems.forEach(btn => {
    btn.addEventListener('click', () => {
      navItems.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      const key = btn.dataset.section;
      const target = sectionMap[key];
      if(target){
        document.querySelector(target)?.scrollIntoView({behavior:'smooth', block:'start'});
      } else {
        window.scrollTo({top:0, behavior:'smooth'});
      }
      if(window.innerWidth <= 760) closeSidebar();
    });
  });

  // Profile dropdown
  const profileMenu = document.getElementById('profileMenu');
  const profileTrigger = document.getElementById('profileTrigger');
  const profileDropdown = document.getElementById('profileDropdown');

  profileTrigger.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle('open');
  });
  document.addEventListener('click', (e) => {
    if(!profileMenu.contains(e.target)) profileDropdown.classList.remove('open');
  });

/* =========================================================================
   MUNICIPAL ANNOUNCEMENTS — edit sample data here for now.
   This array feeds the hero carousel between the Welcome Banner and
   Statistics.

   Each object mirrors one row you'd later fetch from an `announcements`
   MySQL table, e.g.:
     announcements(id, image_url, category, title, description,
                   date_posted, detail_link, is_published, sort_order)

   To wire this up to the database later:
     1. Replace the ANNOUNCEMENTS array below with a fetch() call to a PHP
        endpoint (e.g. /api/announcements.php) that returns the same shape.
     2. Keep the field names identical (image, category, title, desc,
        date, ctaLink) so the render/carousel logic below needs no changes.
     3. The admin/PIO panel would INSERT/UPDATE rows in that table; this
        carousel only needs to read published rows, newest first.
   ========================================================================= */
const ANNOUNCEMENTS = [
  {
    image: "https://images.unsplash.com/photo-1580983230786-4f8f2a4a6a7f?q=80&w=1200&auto=format&fit=crop",
    category: "Advisory",
    title: "Water interruption — Brgy. Daang Amaya I & II",
    desc: "Scheduled water service interruption on August 12, 10:00 AM–4:00 PM, for pipeline maintenance. Residents are advised to store water in advance.",
    date: "August 12, 2026",
    ctaLink: "#announcement-1"
  },
  {
    image: "https://images.unsplash.com/photo-1584515933487-779824d29309?q=80&w=1200&auto=format&fit=crop",
    category: "Health",
    title: "Free medical & dental mission this Saturday",
    desc: "Municipal gymnasium, 8:00 AM–3:00 PM. Bring a valid ID and your barangay certificate to avail of free consultations.",
    date: "August 10, 2026",
    ctaLink: "#announcement-2"
  },
  {
    image: "https://images.unsplash.com/photo-1450101499163-c8848c66ca85?q=80&w=1200&auto=format&fit=crop",
    category: "Service Update",
    title: "Business permit renewal window extended",
    desc: "The deadline for 2026 business permit renewal is extended to August 31. Apply online through the Services tab to skip the queue.",
    date: "August 8, 2026",
    ctaLink: "#announcement-3"
  },
  {
    image: "https://images.unsplash.com/photo-1521791136064-7986c2920216?q=80&w=1200&auto=format&fit=crop",
    category: "Program",
    title: "Livelihood training program — registration open",
    desc: "Free skills training on food processing and handicrafts, open to all Tanza residents. Slots are limited per barangay.",
    date: "August 5, 2026",
    ctaLink: "#announcement-4"
  }
];

const AUTOPLAY_MS = 6000; // set to 0 to disable autoplay

(function initHeroCarousel(){
  const track = document.getElementById('heroTrack');
  const dotsWrap = document.getElementById('heroDots');
  const prevBtn = document.getElementById('heroPrev');
  const nextBtn = document.getElementById('heroNext');
  const carousel = document.getElementById('heroCarousel');
  if(!track) return;

  let current = 0;
  let timer = null;

  track.innerHTML = ANNOUNCEMENTS.map(a => `
    <div class="hero-slide" style="background-image:url('${a.image}')">
      <div class="hero-content">
        <div class="hero-official-badge">🏛 Official Municipal Announcement</div>
        <div class="hero-eyebrow">
          <span class="hero-cat">${a.category}</span>
          <span class="hero-date-sep">•</span>
          <span class="hero-date">${a.date}</span>
        </div>
        <h2 class="hero-heading">${a.title}</h2>
        <p class="hero-desc">${a.desc}</p>
        <a class="hero-cta" href="${a.ctaLink}">View Details →</a>
      </div>
    </div>
  `).join('');

  dotsWrap.innerHTML = ANNOUNCEMENTS.map((_, i) =>
    `<button class="hero-dot${i===0?' active':''}" data-i="${i}" aria-label="Go to announcement ${i+1}"></button>`
  ).join('');

  const dots = dotsWrap.querySelectorAll('.hero-dot');

  function render(){
    track.style.transform = `translateX(-${current * 100}%)`;
    dots.forEach((d,i)=> d.classList.toggle('active', i===current));
  }

  function goToIndex(i){
    current = (i + ANNOUNCEMENTS.length) % ANNOUNCEMENTS.length;
    render();
    restartAutoplay();
  }

  function next(){ goToIndex(current + 1); }
  function prev(){ goToIndex(current - 1); }

  function restartAutoplay(){
    if(timer) clearInterval(timer);
    if(AUTOPLAY_MS > 0){
      timer = setInterval(next, AUTOPLAY_MS);
    }
  }

  prevBtn.addEventListener('click', prev);
  nextBtn.addEventListener('click', next);
  dots.forEach(d => d.addEventListener('click', () => goToIndex(parseInt(d.dataset.i,10))));

  // pause autoplay while the user's cursor is over the carousel
  carousel.addEventListener('mouseenter', () => { if(timer) clearInterval(timer); });
  carousel.addEventListener('mouseleave', restartAutoplay);

  render();
  restartAutoplay();
})();
</script>

</body>
</html>