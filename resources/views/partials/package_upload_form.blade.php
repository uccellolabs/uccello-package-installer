@php($packageInstallerModule = ucmodule('package-installer'))

@if (auth()->user()->canAdmin($domain, $packageInstallerModule))
<form action="{{ ucroute('package-installer.install.zip', $domain, $module) }}" method="post" enctype="multipart/form-data">
    @csrf
    <div class="row">
        <div class="col s10">
            <div class="file-field input-field">
                <div class="btn primary">
                    <i class="material-icons">attachment</i>
                    <input type="file" name="zip_file" accept="application/zip">
                </div>
                <div class="file-path-wrapper">
                    <input class="file-path" type="text" placeholder="{{ uctrans('field.zip_file', $packageInstallerModule) }}">
                </div>
            </div>
        </div>
        <div class="col s2">
            <button class="btn waves-effect green" type="submit" name="action" style="margin-top: 20px">
                <i class="material-icons left">cloud_upload</i>
                {{ uctrans('button.install', $packageInstallerModule)}}
            </button>
        </div>
    </div>
</form>
@endif
