import 'package:eschool/utils/assets.dart';
import 'package:eschool/utils/labelKeys.dart';

class Menu {

  Menu({required this.iconUrl, required this.title});
  final String title;
  final String iconUrl;
}

//To add all more menu here

final List<Menu> homeBottomSheetMenu = [
  Menu(iconUrl: Assets.attendanceIcon, title: attendanceKey),
  Menu(iconUrl: Assets.timetableIcon, title: timeTableKey),
  Menu(iconUrl: Assets.noticeBoardIcon, title: noticeBoardKey),
  Menu(iconUrl: Assets.examIcon, title: examsKey),
  Menu(iconUrl: Assets.resultIcon, title: resultKey),
  Menu(iconUrl: Assets.reportsIcon, title: reportsKey),
  Menu(iconUrl: Assets.parentIcon, title: parentProfileKey),
  Menu(iconUrl: Assets.holidayIcon, title: academicCalendarKey),
  Menu(iconUrl: Assets.settingsIcon, title: settingsKey),
];
