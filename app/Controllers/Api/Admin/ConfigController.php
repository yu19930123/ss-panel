<?php


namespace App\Controllers\Api\Admin;

use Slim\Http\Request;
use Slim\Http\Response;
use App\Controllers\BaseController;
use App\Contracts\Codes\Cfg;
use App\Services\Config\DbConfig;

class ConfigController extends BaseController implements Cfg
{
    private $cfgs = [
        // App base
        self::AppName,
        self::AppLang,
        self::AppUri,
        self::GoogleAnalyticsId,

        // CheckIn and traffic
        self::CheckInMin,
        self::CheckInMax,
        self::CheckInTime,
        self::DefaultTraffic,
        self::DefaultInviteNum,

        // Mu
        self::MuKey,

        // Message
        self::HomeMessage,

        // Mailgun
        self::MailgunKey,
        self::MailgunDomain,
        self::MailgunSender,
    ];

    public function index(Request $request, Response $response, $args)
    {
        $data = [];
        foreach ($this->cfgs as $cfg) {
            $data[$cfg] = db_config($cfg);
        }
        return $this->echoJsonWithData($response, $data);
    }

    public function update(Request $request, Response $response, $args)
    {
        $cfg = $this->getCfg();
        $input = file_get_contents("php://input");
        $arr = json_decode($input, true);
        foreach ($arr as $k => $v) {
            if(!$v){
                continue;
            }
            $cfg->set($k, $v);
        }
        $cfg->flushAll();
        return $this->echoJsonWithData($response);
    }

    /**
     * @return DbConfig
     */
    public function getCfg()
    {
        return app()->make(DbConfig::class);
    }
}