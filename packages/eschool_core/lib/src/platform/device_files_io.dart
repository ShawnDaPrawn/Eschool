import 'dart:io';

import 'package:open_filex/open_filex.dart';
import 'package:path_provider/path_provider.dart';

Future<String> saveDocument(List<int> bytes, String relativePath) async {
  final String directory = (await getApplicationDocumentsDirectory()).path;
  final File file = File('$directory/$relativePath');
  await file.create(recursive: true);
  await file.writeAsBytes(bytes);
  return file.path;
}

Future<bool> open(String pathOrUrl) async {
  final OpenResult result = await OpenFilex.open(pathOrUrl);
  return result.type == ResultType.done;
}
