# Symfony Screen Recorder - Project Setup Guide

## Project Overview

This is a Symfony 7.x web application that allows users to:
- Record their screen using the MediaRecorder API
- Upload video chunks asynchronously
- Create and manage tasks associated with recordings
- View all recordings in a gallery

## Technology Stack

### Backend
- **Framework**: Symfony 7.x
- **PHP**: 8.2+
- **Database**: MariaDB (via Doctrine ORM)
- **Queue System**: Symfony Messenger with async message handling
- **CLI**: Symfony CLI (no Docker)

### Frontend
- **CSS Framework**: Bootstrap 5
- **Icon Library**: Font Awesome (via importmap)
- **JavaScript Framework**: Stimulus.js (via importmap/asset mapper)
- **Asset Management**: Symfony Asset Mapper (not webpack)

### Key Libraries
- `symfony/uid`: For UUID generation and storage
- `symfony/messenger`: For async message processing
- `symfony/filesystem`: For secure file and directory operations
- `doctrine/orm`: Object-relational mapping

## Project Structure

```
src/
├── Command/              # CLI commands (e.g., ListTasksCommand)
├── Controller/           # HTTP controllers (RecordingController, TaskController)
├── Entity/               # Doctrine entities (Task, Video, VideoChunk)
├── Form/                 # Symfony forms (TaskType)
├── Message/              # Messenger messages (StitchVideoChunks, DeleteTaskWithVideo, CleanupVideoChunks)
├── MessageHandler/       # Messenger handlers for async processing
├── Repository/           # Doctrine repositories
├── Service/              # Business logic services (VideoFileService for file operations)
└── Traits/               # Reusable traits (TimestampableTrait with lifecycle callbacks)

templates/
├── base.html.twig        # Base template with importmap('app')
├── home.html.twig        # Landing page
├── recording/
│   ├── index.html.twig   # Screen recording page with MediaRecorder API
│   └── gallery.html.twig # Video gallery view
└── task/
    ├── index.html.twig   # Task list/overview
    ├── show.html.twig    # Task detail view
    ├── edit.html.twig    # Task edit form
    └── components/
        └── task_list.html.twig # Reusable task list component

assets/
├── app.js               # Main JS entry point
├── controllers/         # Stimulus controllers
│   ├── recorder_controller.js  # Screen recording logic
│   └── csrf_protection_controller.js
└── styles/
    └── app.css          # Global styles

public/
├── videos/              # Uploaded video files
└── chunks/              # Temporary video chunk storage

var/
└── app.db               # SQLite database (for development)
```

## Database Schema

### Key Entities

#### Task
- `id` (UUID, Primary Key)
- `title` (string)
- `description` (text, nullable)
- `status` (enum: pending, in_progress, completed)
- `priority` (enum: low, medium, high)
- `dueDate` (datetime, nullable)
- `video` (OneToOne relationship with Video)
- `createdAt` (datetime via TimestampableTrait)
- `updatedAt` (datetime via TimestampableTrait)

#### Video
- `id` (UUID, Primary Key)
- `title` (string)
- `description` (text, nullable)
- `filePath` (string) - relative path to stored video
- `fileSize` (integer) - file size in bytes
- `task` (OneToOne inverse relationship with Task)
- `createdAt` (datetime via TimestampableTrait)
- `updatedAt` (datetime via TimestampableTrait)

#### VideoChunk
- `id` (UUID, Primary Key)
- `video` (ManyToOne relationship with Video)
- `chunkNumber` (integer)
- `isLast` (boolean)
- `filePath` (string) - path to chunk file
- `createdAt` (datetime via TimestampableTrait)

## Services

### VideoFileService (`src/Service/VideoFileService.php`)

Handles all file operations for video chunks and final video files using Symfony Filesystem component.

**Key Methods:**
- `saveChunk(string $sessionId, int $chunkNumber, string $content): int` - Save chunk and return file size
- `stitchChunks(array $chunkPaths): array` - Concatenate chunks into final video
- `deleteVideo(string $videoPath): void` - Delete video file
- `deleteChunksForSession(string $sessionId): int` - Delete all chunks for a session
- `getChunkRelativePath()`, `getChunkAbsolutePath()` - Path helpers
- `getVideoAbsolutePath()` - Convert relative path to absolute

**Why Separate Service:**
- Centralizes file handling logic
- Easy to test with mock filesystem
- Configuration via environment variables
- Reusable across controllers and handlers

## Async Message Processing

The application uses Symfony Messenger for async video processing:

### Messages & Handlers

1. **StitchVideoChunks** → **StitchVideoChunksHandler**
   - Triggered when all chunks are uploaded (`isLast` is true)
   - Stitches chunks into final video file using VideoFileService
   - Stores video metadata in database

2. **DeleteTaskWithVideo** → **DeleteTaskWithVideoHandler**
   - Triggered when task is deleted
   - Removes video file from disk using VideoFileService
   - Removes associated database records

3. **CleanupVideoChunks** → **CleanupVideoChunksHandler**
   - Removes temporary chunk files after stitching
   - Called after successful video stitching

### Environment Configuration

Key `.env` variables:
```
DATABASE_URL=mysql://user:password@localhost:3306/symfony_recorder
MESSENGER_TRANSPORT_DSN=doctrine://default
```

**File Path Configuration:**
The `CHUNKS_DIR` and `VIDEOS_DIR` paths are configured in `config/services.yaml` as service container parameters:
```yaml
parameters:
    chunks_dir: '%kernel.project_dir%/public/chunks'
    videos_dir: '%kernel.project_dir%/public/videos'
```

These are injected into `VideoFileService` constructor for absolute, reliable path resolution.

## API Endpoints

### Recording
- `POST /api/upload-chunk` - Upload video chunk
- `GET /recording` - Recording page
- `GET /gallery` - Video gallery

### Tasks
- `GET /tasks` - Task list
- `GET /task/{id}` - View task
- `POST /task` - Create task (from recording upload)
- `GET /task/{id}/edit` - Edit task form
- `POST /task/{id}` - Update task
- `GET /task/{id}/delete` - Delete task (async)

## Frontend Architecture

### Stimulus Controllers

**RecorderController** (`assets/controllers/recorder_controller.js`)
- Manages screen recording with `MediaRecorder` API
- Handles video chunk uploads via FormData
- Manages UI state (start/pause/stop recording)
- Shows upload progress modal
- Dispatches async message to stitch chunks

**CsrfProtectionController**
- Handles CSRF token management

### Asset Management

Using Symfony Asset Mapper (no webpack):
- JavaScript and CSS loaded via `importmap('app')`
- Bootstrap 5 and Font Awesome imported as packages
- CSS classes used directly from Bootstrap

## Important Implementation Details

### UUID Usage
- All entities use UUID (from `symfony/uid`)
- Configured as primary key in Doctrine
- Useful for distributed systems and security

### Traits & Lifecycle Callbacks
The `TimestampableTrait` uses Doctrine lifecycle callbacks:
- `#[ORM\PrePersist]` - Sets `createdAt` on insert
- `#[ORM\PreUpdate]` - Sets `updatedAt` on update

### Icon Library
- Switched from Bootstrap Icons (bi) to Font Awesome (fa-solid/fas)
- All templates use `fa-solid fa-*` for consistency
- Import: `importmap('app')` handles Font Awesome

### Video Storage
- Videos stored in `public/videos/` directory
- Chunks stored temporarily in `public/chunks/`
- Cleanup happens via `CleanupVideoChunksHandler`
- File deletion tied to task deletion via async message

## Setup Instructions

### Prerequisites
- PHP 8.2+
- Composer
- MariaDB/MySQL
- Symfony CLI

### Installation
```bash
# Install dependencies
composer install

# Create database
symfony console doctrine:database:create

# Run migrations
symfony console doctrine:migrations:migrate

# Clear cache
symfony console cache:clear
```

### Running the Application
```bash
# Start Symfony dev server
symfony serve

# In another terminal, consume async messages
symfony console messenger:consume async -vv
```

### Common Commands
```bash
# List last 10 tasks
symfony console app:list-tasks

# Create migration
symfony console make:migration

# Generate entities
symfony console make:entity
```

## Development Notes

### Adding New Features
1. Create entity (if needed) - use UUID for ID
2. Create form type (if user input needed)
3. Create controller action
4. Create template using Bootstrap 5 + Font Awesome
5. If async processing needed, create Message + Handler

### Testing
- No test suite currently configured
- Manual testing via browser recommended

### Common Issues

**"Failed to find driver" error**
- Verify `.env` DATABASE_URL points to MariaDB, not PostgreSQL
- Check database credentials

**Video upload fails**
- Ensure `public/chunks/` and `public/videos/` directories exist and are writable
- Check Symfony `var/` directory permissions

**Chunks not stitching**
- Verify messenger consumer is running
- Check `isLast` flag is set correctly on final chunk
- Monitor handler logs for errors

## Git Workflow

Files to ignore:
- `var/` - cache and logs
- `vendor/` - composer packages
- `.env.local` - local environment variables
- `public/videos/` - generated files
- `public/chunks/` - temporary files

## Next Steps / TODOs

- [ ] Add unit/integration tests
- [ ] Implement video compression
- [ ] Add user authentication
- [ ] Add video sharing features
- [ ] Implement storage cleanup jobs
- [ ] Add activity logging

---

**Last Updated**: 2026-02-13
**Maintained By**: Development Team

