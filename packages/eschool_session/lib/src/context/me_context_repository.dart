import 'dart:convert';

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_session/src/auth/session_repository.dart';
import 'package:eschool_session/src/context/me_context.dart';
import 'package:hive/hive.dart';

/// Reads `GET /api/v2/me/context`.
abstract interface class MeContextRepository {
  /// The signed-in user's context, or `null` when the server is older than
  /// API v2 and has no such route.
  Future<MeContext?> fetchContext();
}

/// [MeContextRepository] through the app's [ApiClient], so the call carries
/// the same token, cookies and 401 handling as every other call.
class ApiMeContextRepository implements MeContextRepository {
  ApiMeContextRepository(this._client, {String? url})
    : url = url ?? '${AppConfig.apiV2Url}me/context';

  final ApiClient _client;
  final String url;

  @override
  Future<MeContext?> fetchContext() async {
    final Map<String, dynamic>? result = await _client.getIfSupported(
      url: url,
      useAuthToken: true,
    );
    if (result == null) {
      return null;
    }
    final Object? data = result['data'];
    return MeContext.fromJson(
      data is Map ? Map<String, dynamic>.from(data) : const {},
    );
  }
}

/// Keeps the last context, so the app has it at start-up before the server
/// answers, and offline, with when it was fetched, so the app knows when it
/// is worth asking again.
abstract interface class MeContextCache {
  MeContext? read();

  /// When the kept context was fetched; `null` when there is none, or it was
  /// kept by a version of the app that did not record it.
  DateTime? fetchedAt();

  Future<void> write(MeContext context, {DateTime? fetchedAt});
  Future<void> clear();
}

/// [MeContextCache] in the `authBox`, next to the session it belongs to.
class HiveMeContextCache implements MeContextCache {
  HiveMeContextCache({Box<dynamic>? box}) : _box = box;

  final Box<dynamic>? _box;

  Box<dynamic> get _authBox => _box ?? Hive.box(SessionBoxKeys.authBox);

  @override
  MeContext? read() {
    final Object? stored = _authBox.get(SessionBoxKeys.meContext);
    if (stored is! String || stored.isEmpty) {
      return null;
    }
    try {
      return MeContext.fromJson(
        Map<String, dynamic>.from(jsonDecode(stored) as Map),
      );
    } catch (_) {
      return null;
    }
  }

  @override
  DateTime? fetchedAt() {
    final Object? stored = _authBox.get(SessionBoxKeys.meContextFetchedAt);
    return stored is int ? DateTime.fromMillisecondsSinceEpoch(stored) : null;
  }

  @override
  Future<void> write(MeContext context, {DateTime? fetchedAt}) async {
    await _authBox.put(SessionBoxKeys.meContext, jsonEncode(context.toJson()));
    await _authBox.put(
      SessionBoxKeys.meContextFetchedAt,
      (fetchedAt ?? DateTime.now()).millisecondsSinceEpoch,
    );
  }

  @override
  Future<void> clear() async {
    await _authBox.delete(SessionBoxKeys.meContext);
    await _authBox.delete(SessionBoxKeys.meContextFetchedAt);
  }
}

class InMemoryMeContextCache implements MeContextCache {
  MeContext? stored;
  DateTime? storedAt;

  @override
  MeContext? read() => stored;

  @override
  DateTime? fetchedAt() => storedAt;

  @override
  Future<void> write(MeContext context, {DateTime? fetchedAt}) async {
    stored = context;
    storedAt = fetchedAt ?? DateTime.now();
  }

  @override
  Future<void> clear() async {
    stored = null;
    storedAt = null;
  }
}
