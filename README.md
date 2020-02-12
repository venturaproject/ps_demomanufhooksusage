# ps_demomanufhooksusage
demo module to add a new field in Manufacturers

Once added the module in the /modules directory, run
```sh
composer dump-autoload
```


In order to see the new field included add the hook in the template

```sh
  {hook h='displayManufUsageValue'} 
```
