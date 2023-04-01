# Database_displayer_2
Prohlížeč databáze postavený na OOP PHP 8.1 a mysql.  
**Nejnovější verze se nachází v branch latest**.  

## Zprovoznění
1. nainstalovat `composer` - https://getcomposer.org/download/
2. zadání `composer update` do konzole s projektem  
3. upravení config/config souboru  
Řetězce ve tvaru "\**text\**" je třeba upravit  
```json
{
  "app": {
    "lang" : "cs"
  },
  "db": {
    "host" : "127.0.0.1",
    "database" : "**jméno databáze**",
    "charset" : "**znaková sada**",
    "user" : "fill in local config",
    "password" : "fill in local config"
  }
}
```

4. přidání config/config_local  
Řetězce ve tvaru "\**text\**" je třeba upravit  
```json
{
    "db": {
      "user" : "**username**",
      "password" : "**password**"
    }
  }
```
