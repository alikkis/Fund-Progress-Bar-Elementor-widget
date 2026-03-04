# Fund Progress Bar – Elementor Widget

An animated Elementor widget that fetches a live fundraising percentage from
`endpoint` and displays it as a beautiful,
animated progress bar (circular ring and/or horizontal bar).

---

## Installation

1. Download / clone this folder.
2. Zip the **entire `fund-progress-bar` folder**.
3. In your WordPress Admin go to **Plugins → Add New → Upload Plugin**.
4. Upload the ZIP and click **Activate**.
5. Open any page in **Elementor Editor** and search for **"Fund Progress Bar"**
   in the widget panel (under the *General* category).
6. Drag it onto the canvas and customise in the left panel.

---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | ≥ 5.6 |
| PHP | ≥ 7.4 |
| Elementor (free) | ≥ 3.0 |

---

## Widget Controls

### Content Tab
| Setting | Default | Description |
|---|---|---|
| Title | "Fundraising Goal" | Heading above the bar |
| Subtitle | "Help us reach our target!" | Smaller text |
| API Endpoint | `https://` | URL returning `{"percentage": N}` |
| Auto-refresh (s) | 60 | 0 = disabled |
| Bar Style | Both | Linear / Circular / Both |
| Animation Duration (ms) | 1800 | How long the fill animation plays |

### Style Tab
- Fill colour, track colour, card background
- Linear bar height, circle diameter, stroke width
- Card box-shadow
- Title & subtitle typography / colour

---

## API Response Format

```json
{ "percentage": 13.4077655714286 }
```

The widget clamps values to **0 – 100** automatically.

---

## File Structure

```
fund-progress-bar/
├── fund-progress-bar.php          ← Plugin bootstrap
├── widget/
│   └── class-fund-progress-bar-widget.php   ← Elementor widget
└── assets/
    ├── fund-progress-bar.css      ← Styles + animations
    └── fund-progress-bar.js       ← Fetch + animation logic
```

---

## Customisation Tips

- To change the **default API URL** globally, edit `'default' => '...'` inside
  `register_controls()` in the widget class.
- The shimmer animation on the linear bar can be disabled by removing the
  `::after` rule in the CSS file.
- For HTTPS sites fetching an HTTP API, configure a **WordPress proxy** or
  update the API URL to `https://`.

---

## Licence

GPL v2 or later – see <https://www.gnu.org/licenses/gpl-2.0.html>
