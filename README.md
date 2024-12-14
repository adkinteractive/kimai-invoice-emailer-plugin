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

## License

MIT License

Copyright (c) 2024 ADK Interactive, LLC

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
