import 'package:intl/intl.dart' as intl;

extension StringExtension on String {
  String toCapitalized() =>
      length > 0 ? '${this[0].toUpperCase()}${substring(1).toLowerCase()}' : '';
  String toTitleCase() => replaceAll(
    RegExp(' +'),
    ' ',
  ).split(' ').map((str) => str.toCapitalized()).join(' ');

  String commaSeperatedListToString() => substring(
    1,
    length - 1,
  ).split(', ').map((part) => part.toCapitalized()).join(', ');

  bool isFile() => [
    'jpg', 'jpeg', 'png', 'gif', 'bmp', // Image formats
    'mp3', 'wav', 'ogg', 'flac', // Audio formats
    'mp4', 'avi', 'mkv', 'mov', // Video formats
    'pdf', // PDF documents
    'doc', 'docx', 'ppt', 'pptx', 'xls',
    'xlsx', // Microsoft Office documents
    'txt', // Text documents
    'html', 'htm', 'css', 'js', // Web formats
    'zip', 'rar', '7z', 'tar', // Archive formats
  ].contains(toLowerCase().split('.').lastOrNull ?? '');

  bool isImage() => [
    'png',
    'jpg',
    'jpeg',
    'gif',
    'webp',
    'bmp',
    'wbmp',
    'pbm',
    'pgm',
    'ppm',
    'tga',
    'ico',
    'cur',
  ].contains(toLowerCase().split('.').lastOrNull ?? '');

  bool isAudioFile() => [
    'mp3',
    'wav',
    'ogg',
    'aac',
    'flac',
  ].contains(toLowerCase().split('.').lastOrNull ?? '');
}

extension DateTimeExtensions on DateTime {
  bool isSameAs(DateTime date) {
    return year == date.year && month == date.month && day == date.day;
  }

  bool isToday() {
    return isSameAs(DateTime.now());
  }

  bool isYesterday() {
    return isSameAs(DateTime.now().subtract(const Duration(days: 1)));
  }

  bool isCurrentYear() {
    return year == DateTime.now().year;
  }

  String getWeekDayName() {
    return intl.DateFormat.EEEE().format(this);
  }
}
