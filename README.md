# GPU List HTML

PassMark Videocard MegaPage viewer with sorting, filtering, pinning and comparison tables.

## Hosting requirements

- PHP 8.0 or newer
- PHP cURL extension
- outbound HTTPS access to `www.videocardbenchmark.net`
- writable system temporary directory

## Installation

Upload the contents of this directory to one directory on your web hosting and open `index.html`.

The browser requests `api/getdata.php` from the same directory. The PHP endpoint establishes a PassMark session, downloads the current JSON data and caches it in the system temporary directory for 15 minutes.

No database or configuration file is required.

