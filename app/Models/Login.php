

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Login extends Model
{
    protected $table = 'users';
    protected $fillable = ['email', 'password'];
    protected $hidden = ['password'];

    public static function checkCredentials($email, $password)
    {
        $user = self::where('email', $email)->first();
        
        if ($user && Hash::check($password, $user->password)) {
            return $user;
        }
        
        return null;
    }
}