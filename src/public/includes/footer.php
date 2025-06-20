<?php
// Footer component
// Simple footer with copyright and system info
?>

<!-- Footer -->
<footer class="bg-white border-top mt-5 py-3">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <p class="text-muted mb-0">
                    <i class="bi bi-car-front me-1"></i>
                    © <?php echo date('Y'); ?> RNB ParkManager - Sistema de Gestão de Estacionamento
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">
                    <i class="bi bi-clock me-1"></i>
                    Última atualização: <?php echo date('d/m/Y H:i'); ?>
                </small>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>