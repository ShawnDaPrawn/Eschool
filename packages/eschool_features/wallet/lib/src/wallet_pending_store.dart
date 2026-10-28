import 'package:shared_preferences/shared_preferences.dart';

/// Only opaque operation UUIDs are retained, independently per school and actor.
abstract interface class WalletPendingStore {
  Future<Set<String>> read(int school, int actor);
  Future<void> put(int school, int actor, String operation);
  Future<void> remove(int school, int actor, String operation);
}

class SharedPreferencesWalletPendingStore implements WalletPendingStore {
  String _prefix(int school, int actor) =>
      'wallet_pending_v1_${school}_${actor}_';
  bool _uuid(String value) => RegExp(
    r'^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$',
  ).hasMatch(value);

  @override
  Future<Set<String>> read(int school, int actor) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.reload();
    final prefix = _prefix(school, actor);
    return prefs
        .getKeys()
        .where((key) => key.startsWith(prefix))
        .map((key) => key.substring(prefix.length))
        .where(_uuid)
        .toSet();
  }

  @override
  Future<void> put(int school, int actor, String operation) async {
    if (school < 1 || actor < 1 || !_uuid(operation)) {
      throw StateError('Invalid pending wallet scope.');
    }
    final prefs = await SharedPreferences.getInstance();
    if (!await prefs.setBool('${_prefix(school, actor)}$operation', true)) {
      throw StateError('The pending wallet operation could not be retained.');
    }
  }

  @override
  Future<void> remove(int school, int actor, String operation) async {
    final prefs = await SharedPreferences.getInstance();
    if (!await prefs.remove('${_prefix(school, actor)}$operation')) {
      throw StateError('The pending wallet operation could not be cleared.');
    }
  }
}
