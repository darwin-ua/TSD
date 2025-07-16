<footer class="main-footer">
    <div class="float-right d-none d-sm-block">
        <b>Version</b> 3.2.0
    </div>
    <strong><a href="https://adminlte.io"></a></strong>
</footer>

<aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
</aside>
<!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->
<script src="{{ asset('storage/AdminLTE/plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/dist/js/adminlte.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/select2/js/select2.full.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/bootstrap4-duallistbox/jquery.bootstrap-duallistbox.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/moment/moment.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/inputmask/jquery.inputmask.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/daterangepicker/daterangepicker.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/bootstrap-switch/js/bootstrap-switch.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/bs-stepper/js/bs-stepper.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/dropzone/min/dropzone.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/dist/js/all.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/summernote/summernote-bs4.min.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/codemirror/codemirror.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/codemirror/mode/css/css.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/codemirror/mode/xml/xml.js') }}"></script>
<script src="{{ asset('storage/AdminLTE/plugins/codemirror/mode/htmlmixed/htmlmixed.js') }}"></script>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const fileInputTitle = document.getElementById("foto_title");
        const fileInputLabelTitle = document.getElementById("fileInputLabelTitle");
        const fileInputLogo = document.getElementById("foto_logo");
        const fileInputLabelLogo = document.getElementById("fileInputLabelLogo");

        fileInputTitle.addEventListener("change", function () {
            if (fileInputTitle.files.length > 0) {
                fileInputLabelTitle.textContent = fileInputTitle.files[0].name;
            } else {
                fileInputLabelTitle.textContent = "Нажмите здесь для выбора файла";
            }
        });

        fileInputLogo.addEventListener("change", function () {
            if (fileInputLogo.files.length > 0) {
                fileInputLabelLogo.textContent = fileInputLogo.files[0].name;
            } else {
                fileInputLabelLogo.textContent = "Нажмите здесь для выбора файла";
            }
        });
    });
</script>
</body>
</html>



