import 'package:hive/hive.dart';

/// A browser: Hive keeps every box in IndexedDB and needs no directory.
Future<void> initAppStorage(List<String> boxNames) async {}

Future<Box<dynamic>> openAuthStorage(String name) =>
    Hive.openBox<dynamic>(name);
