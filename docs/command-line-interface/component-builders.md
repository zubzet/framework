??? info "Don't have the ZubZet command?"
    If you don't have the ZubZet command already. Refer to the 
    <a href="/setup/how-to-update/#update-from-older-versions-than-0110">legacy update documentation</a>.

## Generate Models
```bash
php zubzet build model SampleName
```
**SampleName** will be used as the name for the new <i>model</i>.

## Generate Views
```bash
php zubzet build view SampleName
```
**SampleName** will be used as the name for the new <i>view</i>.

## Generate Controllers
```bash
php zubzet build controller SampleName
```
**SampleName** will be used as the name for the new <i>controller</i>.

## Generate Actions
```bash
php zubzet build action SampleName index
```
**SampleName** is the name of the controller in question.<br>
**index** will be used as the name for the new <i>action</i>.