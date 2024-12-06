# Invoice Emailer Plugin for Kimai 2

The Invoice Emailer Plugin enhances Kimai's invoicing workflow by enabling automated email communications directly from the invoice list. With this plugin, you can:

- Send invoice emails instantly from the invoice list view
- Configure automatic email sending when invoices are created
- Automatically update invoice status after sending emails

## Key Features

- **Automated Email Workflow**: Automatically send emails when invoices are created in "New" status
- **Status Management**: Automatically transition invoice status (e.g., from "New" to "Pending") after sending emails
- **Built-in Integration**: Uses Kimai's native email system for reliable delivery

> **Note**: Requires proper configuration of Kimai's email settings.

## Installation

This plugin is compatible with the following Kimai releases:

| Bundle version | Minimum Kimai version |
|----------------|-----------------------|
| 1.0 - 1.9      | 2.17                  |

Download and extract the [compatible release](https://github.com/adkinteractive/kimai-invoice-emailer-plugin/releases) in `var/plugins/` (see [plugin docs](https://www.kimai.org/documentation/plugin-management.html)).

The file structure needs to look like this afterwards:

```bash
var/plugins/
├── InvoiceEmailerBundle
│   ├── InvoiceEmailerBundle.php
|   └ ... more files and directories follow here ... 
```

Then rebuild the cache:
```bash
bin/console kimai:reload --env=prod
```

## Installation from Git

To install the plugin from the Git repository, follow these steps:

1. Navigate to the `var/plugins/` directory of your Kimai installation:
    ```bash
    cd /path/to/kimai/var/plugins/
    ```

2. Clone the repository into a folder named `InvoiceEmailerBundle`:
    ```bash
    git clone https://github.com/adkinteractive/kimai-invoice-emailer-plugin InvoiceEmailerBundle
    ```

3. Navigate to the newly created directory:
    ```bash
    cd InvoiceEmailerBundle
    ```

4. Rebuild the cache:
    ```bash
    cd /path/to/kimai
    bin/console kimai:reload --env=prod
    ```