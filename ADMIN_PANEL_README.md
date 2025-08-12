# Laravel Admin Panel

A comprehensive admin panel for managing users, roles, and permissions built with Laravel and Spatie Laravel Permission package.

## Features

- **User Management**: Create, edit, delete, and manage user accounts
- **Role Management**: Define and manage user roles with specific permissions
- **Permission Management**: Create and manage granular permissions
- **Role-Based Access Control**: Secure admin panel with permission-based access
- **Modern UI**: Clean, responsive interface built with Tailwind CSS
- **Dashboard**: Overview with statistics and quick actions

## Default Users

The system comes with pre-configured users for testing:

| Email | Password | Role | Permissions |
|-------|----------|------|-------------|
| superadmin@example.com | password | Super Admin | All permissions |
| admin@example.com | password | Admin | Most admin permissions |
| manager@example.com | password | Manager | Limited admin access |
| editor@example.com | password | Editor | Content management only |
| user@example.com | password | User | Basic access only |

## Default Roles

### Super Admin
- Has access to all permissions
- Can manage everything in the system

### Admin
- Can manage users, roles, and permissions
- Has access to admin panel and dashboard
- Can manage content and settings
- Can view logs

### Manager
- Can view users, roles, and permissions
- Has access to admin panel and dashboard
- Can manage content

### Editor
- Has access to admin panel and dashboard
- Can manage content (view, create, edit, publish)

### User
- Basic user with minimal permissions
- Can only view content

## Default Permissions

### User Management
- `view users` - View user list
- `create users` - Create new users
- `edit users` - Edit existing users
- `delete users` - Delete users

### Role Management
- `view roles` - View role list
- `create roles` - Create new roles
- `edit roles` - Edit existing roles
- `delete roles` - Delete roles

### Permission Management
- `view permissions` - View permission list
- `create permissions` - Create new permissions
- `edit permissions` - Edit existing permissions
- `delete permissions` - Delete permissions

### Admin Panel
- `access admin panel` - Access to admin panel
- `view dashboard` - View admin dashboard

### Content Management
- `view content` - View content
- `create content` - Create new content
- `edit content` - Edit existing content
- `delete content` - Delete content
- `publish content` - Publish content

### System
- `manage settings` - Manage system settings
- `view logs` - View system logs
- `backup system` - Create system backups

## Installation & Setup

1. **Install Dependencies**
   ```bash
   composer install
   npm install
   ```

2. **Environment Setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   php artisan migrate:fresh --seed
   ```

4. **Build Assets**
   ```bash
   npm run build
   ```

5. **Start Development Server**
   ```bash
   php artisan serve
   ```

## Usage

### Accessing the Admin Panel

1. Login with any of the default users (e.g., `superadmin@example.com` / `password`)
2. Navigate to `/admin` to access the admin panel
3. Use the navigation menu to access different sections

### Managing Users

1. Go to **Users** section in the admin panel
2. Click **Add New User** to create a new user
3. Fill in the required information and assign roles
4. Use **Edit** or **Delete** actions to manage existing users

### Managing Roles

1. Go to **Roles** section in the admin panel
2. Click **Add New Role** to create a new role
3. Assign permissions to the role
4. Use **Edit** or **Delete** actions to manage existing roles

### Managing Permissions

1. Go to **Permissions** section in the admin panel
2. Click **Add New Permission** to create a new permission
3. Use **Edit** or **Delete** actions to manage existing permissions

## Routes

### Admin Routes (Protected by `access admin panel` permission)

- `GET /admin` - Admin dashboard
- `GET /admin/users` - List users
- `GET /admin/users/create` - Create user form
- `POST /admin/users` - Store new user
- `GET /admin/users/{user}/edit` - Edit user form
- `PUT /admin/users/{user}` - Update user
- `DELETE /admin/users/{user}` - Delete user
- `GET /admin/roles` - List roles
- `GET /admin/roles/create` - Create role form
- `POST /admin/roles` - Store new role
- `GET /admin/roles/{role}/edit` - Edit role form
- `PUT /admin/roles/{role}` - Update role
- `DELETE /admin/roles/{role}` - Delete role
- `GET /admin/permissions` - List permissions
- `GET /admin/permissions/create` - Create permission form
- `POST /admin/permissions` - Store new permission
- `GET /admin/permissions/{permission}/edit` - Edit permission form
- `PUT /admin/permissions/{permission}` - Update permission
- `DELETE /admin/permissions/{permission}` - Delete permission

## Security Features

- **Permission-based Access**: All admin routes are protected by specific permissions
- **Role-based Authorization**: Users can only access features based on their assigned roles
- **CSRF Protection**: All forms include CSRF tokens
- **Input Validation**: All user inputs are validated
- **Secure Password Handling**: Passwords are properly hashed

## Customization

### Adding New Permissions

1. Add the permission to the `PermissionSeeder`
2. Run `php artisan db:seed --class=PermissionSeeder`
3. Assign the permission to appropriate roles

### Adding New Roles

1. Add the role to the `RoleSeeder`
2. Assign appropriate permissions to the role
3. Run `php artisan db:seed --class=RoleSeeder`

### Extending the Admin Panel

The admin panel is built with a modular structure:

- **Controllers**: `app/Http/Controllers/Admin/`
- **Views**: `resources/views/admin/`
- **Routes**: Defined in `routes/web.php` under the admin group
- **Seeders**: `database/seeders/`

## Troubleshooting

### Common Issues

1. **Permission Denied**: Ensure the user has the `access admin panel` permission
2. **Route Not Found**: Check if the route is properly defined and the middleware is correct
3. **Database Errors**: Run `php artisan migrate:fresh --seed` to reset the database

### Debug Mode

Enable debug mode in `.env`:
```
APP_DEBUG=true
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
