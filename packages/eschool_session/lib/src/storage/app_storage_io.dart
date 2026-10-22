import 'dart:io';

import 'package:hive/hive.dart';
import 'package:path_provider/path_provider.dart';

/// Starts Hive in the app's private support directory. Hive.initFlutter() would
/// use the Documents directory, which iOS shows in the Files app (because
/// UIFileSharingEnabled is on), exposing the boxes, including the login token.
Future<void> initAppStorage(List<String> boxNames) async {
  final Directory hiveDirectory = await getApplicationSupportDirectory();
  await _moveHiveBoxesFromDocuments(hiveDirectory, boxNames);
  Hive.init(hiveDirectory.path);
}

/// Older versions kept the boxes in the Documents directory. Move them over
/// (best effort) so that updating the app does not sign anybody out.
Future<void> _moveHiveBoxesFromDocuments(
  Directory hiveDirectory,
  List<String> boxNames,
) async {
  final Directory documentsDirectory;
  try {
    documentsDirectory = await getApplicationDocumentsDirectory();
    if (documentsDirectory.path == hiveDirectory.path) {
      return;
    }
    await hiveDirectory.create(recursive: true);
  } catch (_) {
    return;
  }

  for (final boxName in boxNames) {
    //Hive stores every box under its lower-case name
    final String name = boxName.toLowerCase();
    try {
      if (await File('${hiveDirectory.path}/$name.hive').exists()) {
        continue;
      }
      for (final fileExtension in ['hive', 'hivec', 'lock']) {
        final File oldFile = File(
          '${documentsDirectory.path}/$name.$fileExtension',
        );
        if (!await oldFile.exists()) {
          continue;
        }
        final String newPath = '${hiveDirectory.path}/$name.$fileExtension';
        try {
          await oldFile.rename(newPath);
        } catch (_) {
          await oldFile.copy(newPath);
          await oldFile.delete();
        }
      }
    } catch (_) {
      //Could not move this box: it starts empty in the new directory
    }
  }
}
