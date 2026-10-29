import 'package:hive/hive.dart';

import 'app_storage_io.dart'
    if (dart.library.js_interop) 'app_storage_web.dart'
    as platform;

/// Starts Hive where the platform keeps the app's boxes (the session among
/// them) out of the user's sight: the app's private support directory on a
/// phone, moving boxes an older version left in Documents, and IndexedDB in
/// a browser. Call it before opening any box.
Future<void> initAppStorage(List<String> boxNames) =>
    platform.initAppStorage(boxNames);

/// Opens the session with OS-backed encryption on native platforms.
Future<Box<dynamic>> openAuthStorage(String name) =>
    platform.openAuthStorage(name);
