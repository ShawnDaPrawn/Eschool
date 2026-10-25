import 'dart:async';

import 'package:dio/dio.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_academics/eschool_academics.dart'
    show SecurePracticeApprovalStore;
import 'package:eschool_session/eschool_session.dart';
import 'package:flutter/foundation.dart';

class StaffSchool {
  const StaffSchool(this.id, this.name, this.slug);
  factory StaffSchool.fromJson(Map<String, dynamic> json) => StaffSchool(
    json['id'] as int,
    json['name'] as String,
    json['slug'] as String,
  );
  final int id;
  final String name, slug;
}

class StaffProfile {
  StaffProfile.fromJson(Map<String, dynamic> json)
    : userId = json['user_id'] as int,
      schoolId = json['school_id'] as int,
      name = json['name'] as String,
      roles = List<String>.from(json['roles'] as List),
      schools = [
        for (final row in json['schools'] as List)
          StaffSchool.fromJson(Map<String, dynamic>.from(row as Map)),
      ],
      features = Map<String, bool>.from(json['features'] as Map) {
    if (userId <= 0 ||
        schoolId <= 0 ||
        roles.isEmpty ||
        !roles.any(
          ['Teacher', 'Facilitator', 'Counsellor', 'Safeguarding'].contains,
        ) ||
        !schools.any((s) => s.id == schoolId)) {
      throw const FormatException('Invalid staff profile');
    }
  }
  final int userId, schoolId;
  final String name;
  final List<String> roles;
  final List<StaffSchool> schools;
  final Map<String, bool> features;
}

class StaffPolicy {
  StaffPolicy.fromJson(Map<String, dynamic> json)
    : document = json['document'] as String,
      version = json['version'] as String,
      title = json['title'] as String,
      text = json['text'] as String;
  final String document, version, title, text;
}

class StaffSignInReply {
  StaffSignInReply.fromJson(Map<String, dynamic> json)
    : authenticated = json['authenticated'] as bool,
      passwordRequired = json['password_change_required'] as bool,
      token = json['token'] as String?,
      profile = json['profile'] == null
          ? null
          : StaffProfile.fromJson(
              Map<String, dynamic>.from(json['profile'] as Map),
            ),
      documents = [
        for (final row in json['documents'] as List)
          StaffPolicy.fromJson(Map<String, dynamic>.from(row as Map)),
      ];
  final bool authenticated, passwordRequired;
  final String? token;
  final StaffProfile? profile;
  final List<StaffPolicy> documents;
}

/// This flavour owns an in-memory session; it never opens legacy teacher boxes
/// or initializes notifications/Firebase. A terminated app signs in again.
class FacilitatorRepository extends ChangeNotifier {
  FacilitatorRepository({AuthMode? mode, Dio Function()? createDio})
    : mode = mode ?? AuthMode.current,
      _createDio = createDio;
  final AuthMode mode;
  final Dio Function()? _createDio;
  String _token = '', preferredSchool = '';
  StaffProfile? profile;
  int? _school;
  int epoch = 0;
  bool busy = false, ready = false;
  bool _disposed = false;
  String? error;
  bool get signedIn => profile != null;
  bool matches(int generation, int school, int user) =>
      ready &&
      generation == epoch &&
      profile?.userId == user &&
      profile?.schoolId == school &&
      _school == school;
  Dio _dio() => withSchoolHeader(
    _createDio?.call() ??
        createSessionDio(mode: mode, apiBaseUrl: AppConfig.apiBaseUrl),
    () => _school,
  );
  ApiClient get client {
    final generation = epoch;
    return ApiClient(
      authToken: () => _token,
      onUnauthorized: () {
        if (epoch == generation) clear();
      },
      sendsBearerToken: mode == AuthMode.bearer,
      createDio: _dio,
    );
  }

  Map<String, dynamic> _headers() => {
    'Accept': 'application/json',
    if (mode == AuthMode.bearer && _token.isNotEmpty)
      'Authorization': 'Bearer $_token',
    if (_school != null) 'X-School-Id': '$_school',
    if (profile != null) 'X-Account-Id': '${profile!.userId}',
  };
  Future<Map<String, dynamic>> _request(
    String method,
    String path, {
    Map<String, dynamic>? data,
    Map<String, dynamic>? headers,
  }) async {
    final cancellation = CancelToken();
    final timer = Timer(
      const Duration(seconds: 30),
      () => cancellation.cancel('Request deadline elapsed'),
    );
    try {
      final result = await _dio().request<Object?>(
        '${AppConfig.apiV2Url}facilitator/$path',
        data: data,
        cancelToken: cancellation,
        options: Options(
          method: method,
          contentType: Headers.jsonContentType,
          headers: headers ?? _headers(),
          validateStatus: (status) => status != null && status < 500,
        ),
      );
      final body = result.data;
      if (body is! Map) {
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      }
      final output = Map<String, dynamic>.from(body);
      if (result.statusCode == 409 &&
          output['code'] == 'FACILITATOR_SETUP_REQUIRED') {
        return output;
      }
      if (result.statusCode != 200 || output['error'] != false) {
        throw ApiException(
          output['message'] is String
              ? output['message'] as String
              : ApiErrorCodes.defaultErrorMessageKey,
          isApiMessage: true,
          statusCode: result.statusCode,
        );
      }
      return output;
    } on DioException catch (e) {
      throw ApiException(
        'Connect to the school and try again.',
        isApiMessage: true,
        statusCode: e.response?.statusCode,
        isTransportFailure: e.response == null,
      );
    } finally {
      timer.cancel();
    }
  }

  @override
  void dispose() {
    epoch++;
    _disposed = true;
    _token = '';
    profile = null;
    _school = null;
    super.dispose();
  }

  void clear() {
    if (_disposed) return;
    epoch++;
    _token = '';
    profile = null;
    _school = null;
    busy = false;
    ready = false;
    error = null;
    notifyListeners();
  }

  Future<StaffSignInReply> signIn(Map<String, dynamic> input) async {
    if (busy) {
      throw ApiException(
        'Wait for the current request to finish.',
        isApiMessage: true,
      );
    }
    if (profile != null || _token.isNotEmpty) {
      clear();
    }
    final generation = epoch;
    busy = true;
    preferredSchool = input['school'] as String;
    notifyListeners();
    try {
      final response = await _request(
        'POST',
        'sign-in',
        data: input,
        headers: {'Accept': 'application/json'},
      );
      if (generation != epoch) {
        throw ApiException(
          'Your account changed. Sign in again.',
          statusCode: 403,
        );
      }
      final result = StaffSignInReply.fromJson(
        Map<String, dynamic>.from(response['data'] as Map),
      );
      if (result.authenticated) {
        final value = result.profile;
        if (value == null ||
            !value.schools.any(
              (s) => s.id == value.schoolId && s.slug == preferredSchool,
            ) ||
            (mode == AuthMode.bearer && (result.token ?? '').isEmpty)) {
          throw ApiException(
            'The school returned a different sign-in context.',
            statusCode: 403,
          );
        }
        profile = value;
        _school = value.schoolId;
        _token = result.token ?? '';
        ready = true;
        busy = false;
        epoch++;
        notifyListeners();
      } else if (result.profile != null || result.token != null) {
        throw ApiException(
          'Unexpected sign-in setup response.',
          statusCode: 403,
        );
      }
      return result;
    } finally {
      if (generation == epoch) {
        busy = false;
        notifyListeners();
      }
    }
  }

  Future<void> refresh() async {
    if (profile == null || busy) return;
    // Recreate private readers even when fresh authority returns the same IDs.
    // Retained rows/drafts must not survive a same-account role refresh.
    epoch++;
    final generation = epoch, account = profile!.userId, school = _school;
    busy = true;
    ready = false;
    error = null;
    notifyListeners();
    try {
      final response = await _request('GET', 'session');
      if (generation != epoch) return;
      final value = StaffProfile.fromJson(
        Map<String, dynamic>.from(response['data'] as Map),
      );
      if (value.userId != account || value.schoolId != school) {
        throw ApiException(
          'Your account changed. Sign in again.',
          statusCode: 403,
        );
      }
      profile = value;
      ready = true;
    } catch (e) {
      if (generation != epoch) return;
      if (e is ApiException && [401, 403, 404].contains(e.statusCode)) {
        clear();
        return;
      }
      error = 'Connect to refresh your staff access.';
    } finally {
      if (generation == epoch) {
        busy = false;
        notifyListeners();
      }
    }
  }

  Future<void> switchSchool(int id) async {
    if (busy || profile == null || id == _school) return;
    final choice = profile!.schools.where((s) => s.id == id).firstOrNull;
    if (choice == null) return;
    final original = profile!;
    unawaited(
      SecurePracticeApprovalStore.revokeScope(
        original.userId,
        original.schoolId,
      ).catchError((Object _) {}),
    );
    preferredSchool = choice.slug;
    epoch++;
    _school = id;
    ready = false;
    notifyListeners();
    await refresh();
  }

  Future<void> signOut() async {
    final headers = _headers();
    final original = profile;
    if (original != null) {
      unawaited(
        SecurePracticeApprovalStore.revokeScope(
          original.userId,
          original.schoolId,
        ).catchError((Object _) {}),
      );
    }
    clear();
    final generation = epoch;
    busy = true;
    notifyListeners();
    try {
      await _request('POST', 'sign-out', headers: headers);
    } catch (_) {
      /* Local access is already cleared, including offline. */
    } finally {
      if (generation == epoch) {
        busy = false;
        notifyListeners();
      }
    }
  }
}
