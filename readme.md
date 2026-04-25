# 📊 WP Dashboard

A stats dashboard that lives right inside your WordPress site. 

## ✨ Features

### 🏠 The Dashboard Page
- 🎨 Sleek dark mode layout that looks great on any screen size
- 📱 Fully responsive — works on desktop, tablet, and mobile
- ⚡ All your site data loaded in a single request
- 🔄 AJAX refresh button that validates fresh data is available before reloading
- 🕒 Shows exactly when your data was last fetched so you always know how fresh it is
- 🔗 One-click button in the dashboard to jump straight back to your WordPress admin
- 🔀 Accessible at `/wpdashboard/` by default
- 🎛️ Change the dashboard URL to anything you like using a single WordPress filter
- ♻️ Automatically registers and cleans up the URL on plugin activate and deactivate
- 🚪 Anyone not logged in is automatically redirected to the WordPress login page
- 🔑 Only WordPress administrators can access the dashboard

### 🧭 Admin Toolbar
- 📍 A Site Dashboard link appears in the WordPress admin toolbar automatically
- 👑 Only shown to administrators
- 🔀 Toolbar link updates automatically if you change the dashboard URL slug via filter
- 🎨 Includes a dashicons chart icon so it stands out in the toolbar

### 🔌 Plugins
- 📋 See every plugin installed on your site in one place
- 🟢 Active plugins clearly highlighted with version numbers and author info
- ⚫ Inactive plugins listed separately so you know what's sitting unused
- 👤 Shows who made each plugin so you always know where it came from

### 🎨 Themes
- 🖼️ All installed themes displayed with screenshots where available
- 👑 Your active theme always shown first and highlighted in pink
- 👶 Child themes clearly labelled so you know the parent relationship
- 🏷️ Version numbers and author info shown for every theme

### 📝 Posts & Pages
- 🔢 Total post and page counts shown at a glance
- 📊 Progress bars showing how many are published versus drafts
- 🕐 Your 5 most recent posts listed with dates, comment counts, and categories
- 🏷️ Status badges on every post — Published, Draft, Pending, Scheduled
- 🖊️ Author avatars shown alongside each recent post

### 💬 Comments
- 📬 Total comment count with a live pending moderation count
- ✅ Recent comments shown with author, date, and approval status
- 🚨 Spam comments clearly flagged in red
- 🔗 Every comment links back to the post it was left on

### 🏥 Site Health
- 🐘 PHP version check with a warning if you're running something outdated
- 🗄️ MySQL or MariaDB version shown — automatically detects which one you're on
- 🔒 HTTPS status so you can instantly see if your site is secure
- 🐛 Debug mode indicator — shows a warning badge if it's accidentally left on
- 🧠 Memory limit and current memory usage shown side by side
- 📤 Maximum upload size and post size so you know your file limits
- ⏱️ Maximum execution time so you can spot if scripts might be timing out
- ⏰ WordPress Cron status
- 🔗 Permalink structure so you know your URL format at a glance
- 💾 Database size so you can keep an eye on storage

### 🖥️ Server Environment
- 🐧 Operating system name and version
- 🌐 Web server type — automatically detects Apache, Nginx, or LiteSpeed
- 🔧 PHP interface type so you know how PHP is being run
- 🗃️ Database extension in use — MySQLi detected automatically
- 🌀 cURL version for when you need to know what's handling HTTP requests
- 💿 Disk space with free space, total space, and a colour-coded usage bar
- 🧩 All PHP extensions shown as badges — green if loaded, grey if not

### 📡 REST API
- 🗂️ One combined endpoint that returns everything in a single request
- 🔍 Individual endpoints for health, plugins, themes, posts, and comments
- 📏 Posts and comments endpoints accept a `limit` parameter so you control how many you get
- 💬 Comments endpoint can be filtered by status — approved, pending, spam, or trash
- 🔒 All API routes are restricted to administrator authentication only
- 🤝 REST API and the built-in dashboard page work independently and simultaneously
