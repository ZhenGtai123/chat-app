# Chat Application Backend

A simple chat application backend built with PHP, Slim Framework, and SQLite.

## Setup Instructions

### Requirements

- PHP 8.1 or higher
- Composer
- SQLite extension enabled

### Installation

1. Clone the repository:
```bash
git clone https://github.com/ZhenGtai123/chat-app.git
cd chat-app
```

2. Install dependencies:
```bash
composer install
```

3. Create and initialize the database:
```bash
php database/init.php
```

4. Start the PHP development server:
```bash
cd public
php -S localhost:8000
```

The application will be available at http://localhost:8000.

## API Documentation

### Authentication

All protected routes require an API token to be sent in the `X-API-Token` header.

### Users

#### Create a User

- **URL**: `/users`
- **Method**: `POST`
- **Authentication**: Not required
- **Body**:
```json
{
  "username": "username"
}
```
- **Response**: 201 Created
```json
{
  "id": 1,
  "username": "username",
  "api_token": "generated_token",
  "created_at": "2023-01-01 00:00:00"
}
```

#### Get a User

- **URL**: `/users/{username}`
- **Method**: `GET`
- **Authentication**: Not required
- **Response**: 200 OK
```json
{
  "id": 1,
  "username": "username",
  "created_at": "2023-01-01 00:00:00"
}
```

### Groups

#### Get All Groups

- **URL**: `/groups`
- **Method**: `GET`
- **Authentication**: Required
- **Response**: 200 OK
```json
[
  {
    "id": 1,
    "name": "Group Name",
    "description": "Group Description",
    "created_by": 1,
    "created_at": "2023-01-01 00:00:00"
  }
]
```

#### Create a Group

- **URL**: `/groups`
- **Method**: `POST`
- **Authentication**: Required
- **Body**:
```json
{
  "name": "Group Name",
  "description": "Group Description"
}
```
- **Response**: 201 Created
```json
{
  "id": 1,
  "name": "Group Name",
  "description": "Group Description",
  "created_by": 1,
  "created_at": "2023-01-01 00:00:00"
}
```

#### Get a Group

- **URL**: `/groups/{id}`
- **Method**: `GET`
- **Authentication**: Required
- **Response**: 200 OK
```json
{
  "id": 1,
  "name": "Group Name",
  "description": "Group Description",
  "created_by": 1,
  "created_at": "2023-01-01 00:00:00",
  "members": [
    {
      "id": 1,
      "username": "username",
      "joined_at": "2023-01-01 00:00:00"
    }
  ]
}
```

#### Join a Group

- **URL**: `/groups/{id}/join`
- **Method**: `POST`
- **Authentication**: Required
- **Response**: 200 OK
```json
{
  "message": "Successfully joined the group"
}
```

### Messages

#### Get Messages in a Group

- **URL**: `/groups/{id}/messages`
- **Method**: `GET`
- **Authentication**: Required
- **Query Parameters**:
  - `limit` (optional): Number of messages to return (default: 100)
  - `offset` (optional): Number of messages to skip (default: 0)
  - `since` (optional): Timestamp to get messages since (format: Y-m-d H:i:s)
- **Response**: 200 OK
```json
{
  "messages": [
    {
      "id": 1,
      "group_id": 1,
      "user_id": 1,
      "username": "username",
      "content": "Message content",
      "created_at": "2023-01-01 00:00:00"
    }
  ],
  "timestamp": "2023-01-01 00:00:00"
}
```

#### Create a Message

- **URL**: `/groups/{id}/messages`
- **Method**: `POST`
- **Authentication**: Required
- **Body**:
```json
{
  "content": "Message content"
}
```
- **Response**: 201 Created
```json
{
  "id": 1,
  "group_id": 1,
  "user_id": 1,
  "content": "Message content",
  "created_at": "2023-01-01 00:00:00"
}
```

## Polling for New Messages

To implement a simple polling mechanism, clients can use the `since` parameter with the `/groups/{id}/messages` endpoint:

1. When the client first loads messages, it receives a `timestamp` in the response.
2. In the next poll, it sends this timestamp as the `since` parameter.
3. The server returns only messages created after that timestamp and a new `timestamp`.
4. The client updates its timestamp and repeats the process.

Example:
```http
GET /groups/1/messages?since=2023-01-01%2000:00:00
```

## Running Tests

```bash
./vendor/bin/phpunit
```

This will run both unit and integration tests.
