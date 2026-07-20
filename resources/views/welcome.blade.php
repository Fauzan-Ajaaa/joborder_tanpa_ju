<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manufactur Application</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            background: url('{{ asset('images/backgrounds/auth-bg.png') }}') center/cover no-repeat fixed;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, rgba(107,63,42,0.55) 0%, rgba(30,15,5,0.45) 100%);
            z-index: 0;
        }

        .particles {
            position: fixed;
            inset: 0;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            border-radius: 50%;
            background: rgba(255,200,100,0.15);
            animation: float linear infinite;
        }

        @keyframes float {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100px) scale(1); opacity: 0; }
        }

        .main {
            position: relative;
            z-index: 10;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            padding-bottom: 140px;
        }

        .card {
            background: rgba(255, 250, 235, 0.9);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 28px;
            padding: 56px 52px;
            max-width: 560px;
            width: 100%;
            text-align: center;
            box-shadow:
                0 32px 80px rgba(0,0,0,0.35),
                0 0 0 1px rgba(255,255,255,0.08) inset,
                0 1px 0 rgba(255,255,255,0.3) inset;
            transform: perspective(1000px) rotateX(0deg);
            animation: cardIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @keyframes cardIn {
            from {
                opacity: 0;
                transform: perspective(1000px) rotateX(8deg) translateY(40px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: perspective(1000px) rotateX(0deg) translateY(0) scale(1);
            }
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 2px;
            text-transform: uppercase;
            padding: 6px 18px;
            border-radius: 100px;
            margin-bottom: 24px;
            box-shadow: 0 4px 15px rgba(245,158,11,0.4);
        }

        h1 {
            font-size: 2.4rem;
            font-weight: 800;
            color: #3e2723;
            line-height: 1.2;
            margin-bottom: 8px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.3);
        }

        .subtitle {
            font-size: 0.9rem;
            color: #8d6e63;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .dev-by-card {
            font-size: 13px;
            color: #212121;
            font-weight: 500;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .divider {
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #f59e0b, #d97706);
            border-radius: 10px;
            margin: 0 auto 28px;
        }

        p {
            font-size: 0.92rem;
            color: #000000;
            line-height: 1.8;
            margin-bottom: 40px;
            font-weight: 600;
            text-shadow: none;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 16px 32px;
            background: linear-gradient(135deg, #b45309, #78350f);
            color: white;
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 1px;
            text-decoration: none;
            border-radius: 14px;
            box-shadow:
                0 8px 30px rgba(120,53,15,0.5),
                0 1px 0 rgba(255,255,255,0.15) inset;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0; left: -100%;
            width: 100%; height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.15), transparent);
            transition: left 0.5s;
        }

        .btn:hover::before { left: 100%; }

        .btn:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 16px 40px rgba(120,53,15,0.6);
        }

        .btn:active { transform: translateY(0) scale(0.98); }

        /* Footer */
        .footer {
            position: fixed;
            bottom: 0; left: 0; right: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px 60px;
            background: linear-gradient(to right, #fff7ed, #fef3c7, #fff7ed);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(251, 191, 36, 0.4);
            z-index: 20;
        }

        .footer-text { font-size: 13px; color: #5c3d2b; line-height: 1.6; }
        .footer-text strong { font-size: 16px; display: block; margin-bottom: 4px; }
        .footer-logos { display: flex; align-items: center; gap: 32px; }
        .footer-logos img { height: 72px; width: auto; }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <!-- Particles -->
    <div class="particles" id="particles"></div>

    <div class="main">
        <div class="card">
            <div class="badge">✦ Selamat Datang ✦</div>
            <h1>MAFF-APP</h1>
            <p class="subtitle">Manufaktur - Sistem Job Order Costing</p>
            <p class="dev-by-card">
                Developed by:<br>
                Dr. Nelsi Wisna, S.E., M.Si. <br>
                Cindy Kartika Putri · Fauzan Abiyyu Aziz <br>
                Hanina Syahida Riyanto · Wa Ode Aura Syania Azzahra
            </p>
            <div class="divider"></div>
            <p>
                MAF-APP hadir sebagai solusi digital untuk membantu pelaku usaha dalam mengelola proses produksi secara terstruktur dan efisien.
            </p>
            <a href="{{ route('login') }}" class="btn">Login →</a>
        </div>
    </div>

    <div class="footer">
        <div class="footer-text">
            <strong>Sistem Informasi Akuntansi</strong>
            Fakultas Ilmu Terapan · Telkom University
        </div>
        <div class="footer-logos">
            <img src="{{ asset('images/logo-telkom.png') }}" alt="Telkom">
            <img src="{{ asset('images/logo-eadt.png') }}" alt="EADT">
        </div>
    </div>

    <script>
        // Generate particles
        const container = document.getElementById('particles');
        for (let i = 0; i < 18; i++) {
            const p = document.createElement('div');
            p.className = 'particle';
            const size = Math.random() * 60 + 20;
            p.style.cssText = `
                width: ${size}px;
                height: ${size}px;
                left: ${Math.random() * 100}%;
                animation-duration: ${Math.random() * 12 + 8}s;
                animation-delay: ${Math.random() * 8}s;
            `;
            container.appendChild(p);
        }

        // 3D tilt effect on card
        const card = document.querySelector('.card');
        document.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            const dx = (e.clientX - cx) / window.innerWidth * 12;
            const dy = (e.clientY - cy) / window.innerHeight * 12;
            card.style.transform = `perspective(1000px) rotateY(${dx}deg) rotateX(${-dy}deg)`;
        });
        document.addEventListener('mouseleave', () => {
            card.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
        });
    </script>
</body>
</html>
