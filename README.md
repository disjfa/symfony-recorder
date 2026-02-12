# 🎥 Screen Recorder Application

A modern Symfony-based web application for recording your screen, organizing recordings, and managing associated tasks.
Built with Bootstrap 5 and Stimulus for a responsive, interactive experience.

## 🎯 Features

### Recording

- **Screen Capture**: Record your entire screen or individual windows using `MediaDevices.getDisplayMedia()`
- **Audio Options**: Toggle system audio capture on/off
- **Codec Selection**: Choose between VP9 (higher quality) and VP8 (standard) codecs
- **Live Preview**: Watch your screen as you record
- **Recording Timer**: Real-time display of recording duration
- **Video Preview**: Preview your recording before uploading
- **Metadata**: Add title and description to recordings

### Video Gallery

- **Browse Recordings**: View all recorded videos in a responsive grid
- **Video Players**: Built-in HTML5 video player for each recording
- **Metadata Display**: See file size and creation date for each video
- **Quick Delete**: Remove unwanted recordings
- **Task Integration**: Link to associated tasks directly from the gallery

### Task Management

- **Auto-Creation**: Automatic task creation when uploading a video
- **Status Tracking**: Pending → In Progress → Completed → Cancelled
- **Priority Levels**: Low, Medium, High priorities
- **Due Dates**: Optional deadline setting
- **Task Filtering**: View tasks by status using tabs
- **Task Details**: Full details page with associated video
- **Task Editing**: Update title, description, status, priority, and due date
- **Task Deletion**: Remove completed or unwanted tasks

### User Interface

- **Responsive Design**: Works on desktop, tablet, and mobile
- **Bootstrap 5**: Modern, accessible UI framework
- **Bootstrap Icons**: Beautiful icon set throughout
- **Navigation**: Intuitive navbar with quick links
- **Modal Dialogs**: For upload confirmation and settings
- **Progress Feedback**: Visual feedback for uploads and actions

## 📋 Requirements

- PHP 8.2 or higher
- Symfony 7.4
- SQLite 3 (for local development)
- Modern web browser with:
    - `navigator.mediaDevices.getDisplayMedia()` support
    - WebM video codec support
    - ES6 JavaScript support

## 🚀 Installation & Setup

### 1. Install Dependencies

```bash
cd symfony-recorder
composer install
```

### 2. Configure Database (Already Done)

The `.env` file is pre-configured to use SQLite:

```dotenv
DATABASE_URL="sqlite:///%kernel.project_dir%/var/app.db"
```

### 3. Run Migrations (Already Done)

The database schema has been created. To verify:

```bash
php bin/console doctrine:migrations:status
```

### 4. Create Required Directories

```bash
mkdir -p public/videos
chmod 755 public/videos
```

### 5. Clear Cache

```bash
php bin/console cache:clear
```

## 🏃 Running the Application

### Using Symfony CLI (Recommended)

[documentation](https://symfony.com/download)

```bash
symfony server:start
```

### Migrating database

```bash
php bin/console doctrine:migrations:migrate
```

### Or check the database manually:

```bash
php bin/console doctrine:schema:update --dump-sql
```

and

```bash
php bin/console doctrine:schema:update --force
````

### Start the Messenger Worker

Open a terminal:

```bash
php bin/console messenger:consume async -vv
```

## 📱 Usage Guide

### Recording Your Screen

1. **Navigate to Recording Page**
    - Click "Record" in the navbar or go to `/recording`

2. **Configure Settings**
    - Enter a title (pre-filled with current date/time)
    - Add optional description
    - Choose to include system audio
    - Select codec quality (VP9 or VP8)

3. **Start Recording**
    - Click "Start Recording"
    - Grant browser permission to record screen
    - Select screen or window to record
    - Your screen preview appears in real-time

4. **Control Recording**
    - Watch the timer count up
    - Click "Pause" to pause/resume
    - Click "Stop Recording" when finished

5. **Upload Recording**
    - Preview your video in the modal
    - Confirm title and description
    - Click "Upload & Create Task"
    - Task is automatically created!

### Managing Videos

1. **Browse Gallery**
    - Go to `/gallery` to see all recordings
    - Videos display with metadata (size, date, duration)
    - Each video shows if a task exists

2. **Delete Videos**
    - Click "Delete" button on any video card
    - Confirm deletion (files are permanently removed)

### Managing Tasks

1. **View All Tasks**
    - Go to `/task` or click "Tasks" in navbar
    - Filter by status using tabs
    - See priority and due date at a glance

2. **View Task Details**
    - Click "View" on any task card
    - See associated video player
    - View all task metadata

3. **Edit Tasks**
    - Click "Edit" on task card or details page
    - Update: title, description, status, priority, due date
    - Click "Save Changes"

4. **Delete Tasks**
    - Click "Delete" button
    - Confirm deletion

## 🗂️ Project Structure

```
symfony-recorder/
├── src/
│   ├── Controller/
│   │   ├── RecordingController.php      # Video recording & upload
│   │   └── TaskController.php            # Task CRUD operations
│   ├── Entity/
│   │   ├── Video.php                     # Video entity with UUID
│   │   └── Task.php                      # Task entity with UUID
│   └── Repository/
│       ├── VideoRepository.php
│       └── TaskRepository.php
│
├── templates/
│   ├── base.html.twig                    # Base layout with navbar
│   ├── home.html.twig                    # Landing page
│   ├── recording/
│   │   ├── index.html.twig               # Recording interface
│   │   └── gallery.html.twig             # Video gallery
│   └── task/
│       ├── index.html.twig               # Task list
│       ├── show.html.twig                # Task detail
│       ├── edit.html.twig                # Task form
│       └── components/
│           └── task_list.html.twig       # Reusable task list
│
├── assets/
│   ├── app.js                            # Main JS entry point
│   ├── styles/app.css                    # Custom styling
│   ├── controllers/
│   │   └── recorder_controller.js        # Stimulus controller
│   └── stimulus_bootstrap.js             # Stimulus setup
│
├── public/
│   ├── index.php                         # App entry point
│   └── videos/                           # Stored recordings
│
├── config/
│   ├── routes.yaml                       # Route config
│   └── routes/
│       └── routing.controllers.yaml      # Attribute-based routes
│
├── migrations/
│   └── Version20260212195616.php         # Database schema
│
└── var/
    ├── app.db                            # SQLite database
    └── cache/                            # Cache directory
```

## 🗄️ Database Schema

### Video Table

| Field       | Type         | Notes                       |
|-------------|--------------|-----------------------------|
| id          | UUID         | Primary key, auto-generated |
| title       | VARCHAR(255) | Recording title             |
| description | TEXT         | Optional description        |
| file_path   | VARCHAR(255) | Path to video file          |
| mime_type   | VARCHAR(50)  | Always video/webm           |
| duration    | INTEGER      | Optional video duration     |
| file_size   | INTEGER      | File size in bytes          |
| created_at  | DATETIME     | Creation timestamp          |
| updated_at  | DATETIME     | Last update timestamp       |

### Task Table

| Field       | Type         | Notes                                      |
|-------------|--------------|--------------------------------------------|
| id          | UUID         | Primary key, auto-generated                |
| title       | VARCHAR(255) | Task title                                 |
| description | TEXT         | Optional description                       |
| status      | VARCHAR(20)  | pending, in_progress, completed, cancelled |
| priority    | VARCHAR(20)  | low, medium, high                          |
| due_date    | DATETIME     | Optional deadline                          |
| created_at  | DATETIME     | Creation timestamp                         |
| updated_at  | DATETIME     | Last update timestamp                      |
| video_id    | UUID         | Foreign key to Video (1:1)                 |

## 🎬 API Endpoints

### Video Management

- `POST /api/upload-video` - Upload a recorded video
- `POST /video/{id}/delete` - Delete a video

### Task Management

- `GET /task` - List all tasks
- `GET /task/{id}` - View task details
- `GET /task/{id}/edit` - Edit task form (GET) / save changes (POST)
- `POST /task/{id}/delete` - Delete a task
- `POST /task/{id}/update-status` - Update task status via AJAX

### Pages

- `GET /` - Home page
- `GET /recording` - Recording interface
- `GET /gallery` - Video gallery

## 🛠️ Technologies Used

### Backend

- **Symfony 7.4** - PHP web framework
- **Doctrine ORM** - Database abstraction layer
- **SQLite** - Local development database
- **PHP 8.2+** - Programming language

### Frontend

- **Bootstrap 5** - CSS framework
- **Bootstrap Icons** - Icon library
- **Stimulus 3.2** - JavaScript framework
- **HTML5 MediaRecorder API** - For screen recording
- **Fetch API** - For AJAX requests

### Tools & Libraries

- **Symfony Maker Bundle** - Code generation
- **Symfony UID** - UUID support
- **Twig** - Template engine

## 🔐 Security Considerations

- ✅ CSRF protection (built into Symfony forms if used)
- ✅ File upload validation
- ⚠️ No authentication (can be added easily)
- ⚠️ No authorization checks (all users see all videos/tasks)
- ⚠️ Videos stored in public directory (consider CDN for production)

### Future Security Enhancements

- User authentication
- Role-based access control
- Video encryption
- Input sanitization
- Rate limiting on uploads

## 🚀 Production Deployment

### Considerations

1. **Database**: Switch from SQLite to PostgreSQL/MySQL
2. **Storage**: Use AWS S3 or similar for video files
3. **Video Processing**: Add FFmpeg for thumbnail/preview generation
4. **Authentication**: Implement user login system
5. **HTTPS**: Always use HTTPS in production
6. **Environment Variables**: Use `.env.local` for secrets
7. **File Permissions**: Properly restrict file access

### Docker Deployment

```bash
docker-compose up -d
# Inside container:
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php bin/console assets:install public
```

## 📊 Performance Tips

1. **Video Storage**: Consider storing videos on a CDN
2. **Database Indexing**: Add indexes on frequently queried columns
3. **Caching**: Enable HTTP caching for videos
4. **Compression**: Use FFmpeg to reduce video file sizes
5. **Lazy Loading**: Load video thumbnails on demand

## 🐛 Troubleshooting

### "No video data received" error

- Check browser console for errors
- Ensure `getDisplayMedia()` permission was granted
- Try a different browser

### Video won't upload

- Check file permissions on `public/videos/` directory
- Verify disk space is available
- Check browser network tab for errors

### Tasks not showing

- Run migrations: `php bin/console doctrine:migrations:migrate`
- Clear cache: `php bin/console cache:clear`
- Check database exists: `ls -la var/app.db`

### Browser doesn't support screen recording

- Update to a modern browser (Chrome, Firefox, Edge, Safari 13+)
- Check browser's permissions for screen capture

## 📚 Further Development

### Suggested Enhancements

1. **User System**
   ```bash
   composer require symfony/security-bundle
   php bin/console make:user
   ```

2. **Video Processing**
   ```bash
   composer require symfony/process
   # Add FFmpeg integration
   ```

3. **File Storage**
   ```bash
   composer require league/flysystem-aws-s3-v3
   # Configure S3 storage
   ```

4. **API Documentation**
   ```bash
   composer require nelmio/api-doc-bundle
   # Add Swagger/OpenAPI docs
   ```

5. **Testing**
   ```bash
   composer require --dev phpunit/phpunit
   php bin/console make:test
   ```

## 📞 Support

For issues or questions:

1. Check the troubleshooting section
2. Review browser console for errors
3. Check Symfony logs: `tail -f var/log/dev.log`
4. Review database migrations: `php bin/console doctrine:migrations:status`

## 📄 License

This project is created for educational purposes.

---

**Version**: 1.0  
**Last Updated**: February 12, 2026  
**Status**: Ready for use ✅

