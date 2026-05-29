    <footer class="footer mt-auto py-3 bg-dark text-white">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <span>© 2024 GVA Company - Todos os direitos reservados</span>
            </div>
            <div class="col-md-4 text-end">
                <a href="#top" class="btn btn-outline-light btn-sm">
                    <i class="bi bi-arrow-up-square-fill"></i> Voltar ao topo
                </a>
            </div>
        </div>
    </div>
</footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        $(document).ready(function() {
            $('.table').DataTable({
                dom: 'Bfrtip',
                buttons: ['csv'],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/pt-BR.json'
                },
                pageLength: 50,
                order: [[0, 'desc']]
            });
        });
    </script>

</body>
</html>