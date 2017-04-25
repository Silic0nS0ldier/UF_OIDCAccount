# UF_OIDCAccount
Alternative to UF account system. Offloads login to identity provider supporting OAuth 2 and Open ID Connect of your choice.

- Evolutions of UF features
    - More readable classMapper alternative.
    ```php
    $user = $container->dbModel->User::find(32);
    $newUser = new $container->dbModel->User([
        'detail1' => 'hello'
    ]);
    ```
    - Debuggable authentication system.<br/>
    The built in account system in UF uses `eval` to execute permission callbacks. As `eval` is largely sandboxed, crashes can cause irregular behaviour and cannot be logged. To resolve this issue, the 'permissions' table has a 'callback' and 'values' column.
    ```json
    [
        {
            "default": "value",
            "_name": "field_name"
        },
        "value",
        {
            "_name": "field_name"
        }
    ]
    ```
    `_name` is used to reduce likelihood of property name conflicts.