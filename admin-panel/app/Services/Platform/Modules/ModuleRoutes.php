<?php

declare(strict_types=1);

namespace App\Services\Platform\Modules;

use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;

/** One mapping covers panel and both API generations, including direct URLs. */
class ModuleRoutes
{
    public static function apply(RouteCollection $routes): void
    {
        foreach ($routes as $route) {
            foreach (['student' => 'learner', 'parent' => 'parent', 'teacher' => 'teacher'] as $prefix => $portal) {
                if (str_starts_with($route->uri(), 'api/'.$prefix.'/')) {
                    $route->middleware('module:web_'.$portal.',portal');
                }
            }
            if ($route->uri() === 'api/v2/me/context') {
                $route->middleware('module:web_current,portal');
            }
            $module = self::module($route);
            if ($module !== null) {
                $route->middleware('module:'.$module.(self::isRead($route) ? ',read' : ',write'));
            }
        }
    }

    public static function module(Route $route): ?string
    {
        $action = $route->getActionName();
        if (str_contains($action, '\\Fees\\') || str_contains($action, '\\FeesController@') || str_contains($action, '@getFeesReport') || str_contains($action, '@feesCollectionWidget')) {
            return 'fees';
        }
        if (str_contains($action, '\\OnlineExam') || str_contains($action, '\\OnlineExamQuestion')) {
            return 'online_exams';
        }
        if (str_contains($action, '\\ChatController@') || preg_match('/@(chat_setting_index|chat_setting_update|delete_chat_messages)$/', $action)) {
            return 'chat';
        }
        if (str_contains($action, '\\IdCardController@') || preg_match('/@(getIdCardPdf|staffIdCardList|generateStaffIdCard|teacherIdCardList|generateTeacherIdCard)$/', $action)) {
            return 'cards';
        }
        if (str_contains($action, '\\OnlineRegistrationController@') || preg_match('/@(registrationIndex|studentRegistration)$/', $action)) {
            return 'intake';
        }

        return null;
    }

    private static function isRead(Route $route): bool
    {
        // The seed includes a GET that sends mail and POST status/history
        // queries; classify those explicitly, not by guessed method names.
        $action = $route->getActionMethod();
        if ($action === 'sendFeeNotification') {
            return false;
        }

        return in_array('GET', $route->methods(), true)
            || in_array($action, ['getUserChatMessage', 'getPaymentStatus', 'generateIdCard', 'generateStaffIdCard', 'generateTeacherIdCard'], true);
    }
}
