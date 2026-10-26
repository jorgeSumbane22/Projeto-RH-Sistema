<?php
/**
 * footer.php - Rodapé profissional do sistema
 * Incluído em todas as páginas após o conteúdo principal
 */
?>

<!-- ============================================================
     FOOTER PROFISSIONAL
     ============================================================ -->
<footer class="rh-footer">
    <div class="container-fluid px-4">
        <div class="rh-footer-inner">
            <!-- Coluna 1: Logo e descrição -->
            <div class="rh-footer-col rh-footer-brand">
                <div class="footer-logo">
                    <i class="bi bi-people-fill"></i>
                    <span>Sistema de RH</span>
                </div>
                <p class="footer-description">
                    Plataforma completa de gestão de recursos humanos, 
                    desenvolvida para otimizar processos e conectar pessoas.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-link" title="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" class="social-link" title="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <a href="#" class="social-link" title="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" class="social-link" title="YouTube"><i class="bi bi-youtube"></i></a>
                    <a href="#" class="social-link" title="GitHub"><i class="bi bi-github"></i></a>
                </div>
            </div>

            <!-- Coluna 2: Links rápidos -->
            <div class="rh-footer-col">
                <h6 class="footer-title">Links rápidos</h6>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/modules/dashboard/index.php"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                    <li><a href="<?= BASE_URL ?>/modules/funcionarios/index.php"><i class="bi bi-people"></i> Funcionários</a></li>
                    <li><a href="<?= BASE_URL ?>/modules/relatorios/index.php"><i class="bi bi-graph-up"></i> Relatórios</a></li>
                    <li><a href="<?= BASE_URL ?>/modules/recrutamento/index.php"><i class="bi bi-briefcase"></i> Recrutamento</a></li>
                </ul>
            </div>

            <!-- Coluna 3: Módulos -->
            <div class="rh-footer-col">
                <h6 class="footer-title">Módulos</h6>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>/modules/presenca/index.php"><i class="bi bi-clock-history"></i> Presença</a></li>
                    <li><a href="<?= BASE_URL ?>/modules/folha-pagamento/index.php"><i class="bi bi-cash-stack"></i> Folha</a></li>
                    <li><a href="<?= BASE_URL ?>/modules/avaliacao/index.php"><i class="bi bi-star"></i> Avaliações</a></li>
                    <li><a href="<?= BASE_URL ?>/modules/documentos/index.php"><i class="bi bi-file-earmark"></i> Documentos</a></li>
                </ul>
            </div>

            <!-- Coluna 4: Contato e informações -->
            <div class="rh-footer-col">
                <h6 class="footer-title">Contato</h6>
                <ul class="footer-contact">
                    <li>
                        <i class="bi bi-envelope"></i>
                        <a href="mailto:rh@empresa.com">rh@empresa.com</a>
                    </li>
                    <li>
                        <i class="bi bi-telephone"></i>
                        <a href="tel:+258841234567">+258 84 123 4567</a>
                    </li>
                    <li>
                        <i class="bi bi-geo-alt"></i>
                        <span>Maputo, Moçambique</span>
                    </li>
                    <li>
                        <i class="bi bi-clock"></i>
                        <span>Seg-Sex 08:00 - 17:00</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom bar -->
        <div class="rh-footer-bottom">
            <div class="rh-footer-bottom-left">
                <span>&copy; <?= date('Y') ?> <strong>Sistema de RH</strong> — Todos os direitos reservados.</span>
            </div>
            <div class="rh-footer-bottom-right">
                <span class="badge-version">
                    <i class="bi bi-tag"></i> v2.0.1
                </span>
                <span class="badge-status">
                    <span class="status-dot"></span> Sistema online
                </span>
                <a href="#" class="footer-link">Política de privacidade</a>
                <a href="#" class="footer-link">Termos de uso</a>
            </div>
        </div>

        <!-- Developer Credits -->
        <div class="rh-footer-dev">
            <div class="rh-footer-dev-inner">
                <span class="dev-text">
                    <i class="bi bi-code-square"></i> Desenvolvido por 
                    <strong>Jorge Sumbane</strong>
                </span>
            </div>
        </div>
    </div>
</footer>

<style>
/* ============================================================
   FOOTER PROFISSIONAL - DESIGN PREMIUM
   ============================================================ */

.rh-footer {
    background: var(--rh-card-bg);
    border-top: 1px solid var(--rh-border);
    padding: 3rem 0 0;
    margin-top: 3rem;
    transition: all 0.3s ease;
    box-shadow: 0 -4px 24px rgba(0,0,0,0.04);
}

.rh-footer .rh-footer-inner {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr 1.5fr;
    gap: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid var(--rh-border);
}

/* ----- Colunas ----- */
.rh-footer-col {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

/* ----- Logo ----- */
.footer-logo {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 1.2rem;
    font-weight: 700;
    color: var(--rh-text-primary);
}

.footer-logo i {
    color: var(--rh-accent);
    font-size: 1.4rem;
}

.footer-description {
    font-size: 0.85rem;
    color: var(--rh-text-muted);
    line-height: 1.6;
    margin: 0;
    max-width: 300px;
}

/* ----- Social Links ----- */
.footer-social {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.25rem;
}

.social-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid var(--rh-border);
    color: var(--rh-text-muted);
    transition: all 0.3s ease;
    text-decoration: none;
    font-size: 0.9rem;
}

.social-link:hover {
    background: var(--rh-primary);
    border-color: var(--rh-primary);
    color: #fff;
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(21,94,117,0.25);
}

/* ----- Footer Titles ----- */
.footer-title {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--rh-text-muted);
    font-weight: 700;
    margin: 0 0 0.25rem 0;
}

/* ----- Footer Links ----- */
.footer-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.4rem;
}

.footer-links li {
    margin: 0;
}

.footer-links a {
    color: var(--rh-text-muted);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

.footer-links a i {
    font-size: 0.75rem;
    color: var(--rh-accent);
    opacity: 0.6;
    transition: all 0.3s ease;
}

.footer-links a:hover {
    color: var(--rh-primary);
}

.footer-links a:hover i {
    opacity: 1;
    transform: translateX(2px);
}

/* ----- Contact ----- */
.footer-contact {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.footer-contact li {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.85rem;
    color: var(--rh-text-muted);
}

.footer-contact li i {
    color: var(--rh-accent);
    font-size: 0.9rem;
    width: 18px;
    text-align: center;
}

.footer-contact li a {
    color: var(--rh-text-muted);
    text-decoration: none;
    transition: all 0.3s ease;
}

.footer-contact li a:hover {
    color: var(--rh-primary);
}

.footer-contact li span {
    color: var(--rh-text-muted);
}

/* ----- Bottom Bar ----- */
.rh-footer-bottom {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
    padding: 1.25rem 0 0.75rem;
    font-size: 0.75rem;
    border-bottom: 1px solid var(--rh-border);
}

.rh-footer-bottom-left {
    color: var(--rh-text-muted);
}

.rh-footer-bottom-left strong {
    color: var(--rh-text-primary);
}

.rh-footer-bottom-right {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

/* ----- Developer Credits ----- */
.rh-footer-dev {
    padding: 0.75rem 0 1.5rem;
}

.rh-footer-dev-inner {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.75rem;
}

.rh-footer-dev-inner .dev-text {
    color: var(--rh-text-muted);
    display: flex;
    align-items: center;
    gap: 0.4rem;
}

.rh-footer-dev-inner .dev-text i {
    color: var(--rh-accent);
}

.rh-footer-dev-inner .dev-text strong {
    color: var(--rh-text-primary);
    background: linear-gradient(135deg, var(--rh-primary), var(--rh-accent));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.rh-footer-dev-inner .dev-tech {
    color: var(--rh-text-muted);
    font-size: 0.7rem;
    display: flex;
    align-items: center;
    gap: 0.3rem;
}

.rh-footer-dev-inner .dev-tech i {
    color: var(--rh-accent);
    font-size: 0.6rem;
}

/* ----- Badges ----- */
.badge-version {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.2rem 0.6rem;
    border-radius: 50px;
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
    font-size: 0.7rem;
    font-weight: 600;
}

.badge-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.2rem 0.6rem;
    border-radius: 50px;
    background: rgba(20,184,166,0.12);
    color: var(--rh-accent);
    font-size: 0.7rem;
    font-weight: 600;
}

.status-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--rh-accent);
    animation: pulse-dot 2s infinite;
}

@keyframes pulse-dot {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(0.8); }
}

/* ----- Footer Links ----- */
.footer-link {
    color: var(--rh-text-muted);
    text-decoration: none;
    transition: all 0.3s ease;
    font-size: 0.7rem;
}

.footer-link:hover {
    color: var(--rh-primary);
}

/* ============================================================
   DARK MODE AJUSTES
   ============================================================ */
[data-bs-theme="dark"] .rh-footer {
    background: var(--rh-card-bg);
    border-top-color: var(--rh-border);
}

[data-bs-theme="dark"] .rh-footer-bottom {
    border-top-color: var(--rh-border);
}

[data-bs-theme="dark"] .badge-version {
    background: var(--bs-tertiary-bg);
    color: var(--rh-text-muted);
}

[data-bs-theme="dark"] .badge-status {
    background: rgba(20,184,166,0.15);
}

[data-bs-theme="dark"] .social-link:hover {
    background: var(--rh-primary);
}

[data-bs-theme="dark"] .rh-footer-dev-inner .dev-text strong {
    background: linear-gradient(135deg, #0e7490, #14b8a6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* ============================================================
   RESPONSIVE
   ============================================================ */
@media (max-width: 1199.98px) {
    .rh-footer .rh-footer-inner {
        grid-template-columns: 1.5fr 1fr 1fr 1fr;
    }
}

@media (max-width: 991.98px) {
    .rh-footer .rh-footer-inner {
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
    }
    
    .rh-footer-brand {
        grid-column: 1 / -1;
    }
    
    .footer-description {
        max-width: 100%;
    }
}

@media (max-width: 767.98px) {
    .rh-footer {
        padding: 2rem 0 0;
        margin-top: 2rem;
    }
    
    .rh-footer .rh-footer-inner {
        grid-template-columns: 1fr;
        gap: 1.5rem;
        padding-bottom: 1.5rem;
    }
    
    .rh-footer-bottom {
        flex-direction: column;
        text-align: center;
        gap: 0.75rem;
        padding: 1rem 0 0.5rem;
    }
    
    .rh-footer-bottom-right {
        justify-content: center;
    }
    
    .rh-footer-col {
        align-items: center;
        text-align: center;
    }
    
    .footer-links {
        align-items: center;
    }
    
    .footer-contact li {
        justify-content: center;
    }
    
    .footer-social {
        justify-content: center;
    }
    
    .footer-description {
        text-align: center;
    }
    
    .rh-footer-dev-inner {
        flex-direction: column;
        text-align: center;
        gap: 0.3rem;
    }
}

@media (max-width: 575.98px) {
    .rh-footer-bottom-right {
        gap: 0.5rem;
    }
    
    .footer-link {
        font-size: 0.65rem;
    }
    
    .badge-version,
    .badge-status {
        font-size: 0.6rem;
        padding: 0.15rem 0.5rem;
    }
    
    .rh-footer {
        padding: 1.5rem 0 0;
    }
    
    .rh-footer-dev-inner .dev-text {
        font-size: 0.7rem;
    }
    
    .rh-footer-dev-inner .dev-tech {
        font-size: 0.6rem;
    }
}

/* ============================================================
   PRINT
   ============================================================ */
@media print {
    .rh-footer {
        display: none !important;
    }
}
</style>

<!-- ============================================================
     SCRIPTS GLOBAIS
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/scripts.js"></script>
<script src="<?= BASE_URL ?>/assets/js/dashboard.js"></script>
<script src="<?= BASE_URL ?>/assets/js/charts.js"></script>
<script src="<?= BASE_URL ?>/assets/js/validation.js"></script>
</body>
</html>