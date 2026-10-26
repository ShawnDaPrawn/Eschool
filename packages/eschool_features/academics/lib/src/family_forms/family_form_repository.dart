import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';

abstract interface class FamilyFormRepository {
  int get schoolId;
  int get accountId;
  bool get isActive;
  Future<FamilyFormConfiguration> configuration();
  Future<FamilyConsentPage> listing({
    int? learnerId,
    int? groupId,
    int page = 1,
  });
  Future<FamilyConsentDetail> read(String id, int learnerId);
  Future<FamilyConsentManaged> managed(String id, int groupId, {int page = 1});
  Future<FamilyConsentSigned> sign(
    String id,
    int learnerId,
    FamilyConsentSign intent,
  );
  Future<FamilyConsentCancelled> cancel(
    String id,
    String operation,
    int revision,
  );
  Future<ProfileForm> health(int learnerId, int personId);
  Future<ProfileForm> saveHealth(
    int learnerId,
    int personId,
    Map<String, ProfileValue> values,
  );
  Future<void> uploadHealth(
    int learnerId,
    int personId,
    String field,
    String name,
    Uint8List bytes,
  );
  Future<Uint8List> healthFile(
    int learnerId,
    int personId,
    ProfileAttachment attachment,
  );
}

/// Every request is private and bound to one school, account and authorization epoch.
class ApiFamilyFormRepository implements FamilyFormRepository {
  ApiFamilyFormRepository(
    this.client, {
    required this.schoolId,
    required this.accountId,
    required this.contextActive,
  }) : _token = client.authToken();
  final ApiClient client;
  @override
  final int schoolId, accountId;
  final bool Function() contextActive;
  final String _token;
  @override
  bool get isActive =>
      schoolId > 0 &&
      accountId > 0 &&
      contextActive() &&
      (!client.sendsBearerToken || client.authToken() == _token);
  Never _denied() => throw ApiException(
    'Your family access changed. Reopen this screen.',
    statusCode: 403,
    isApiMessage: true,
  );
  void _guard() {
    if (!isActive) _denied();
  }

  void _id(String id) {
    if (!RegExp(
      r'^[a-f0-9]{8}-[a-f0-9]{4}-[1-8][a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$',
    ).hasMatch(id)) {
      throw ArgumentError.value(id);
    }
  }

  Future<Map<String, dynamic>> _send(
    String path, {
    String method = 'GET',
    Object? body,
    Map<String, dynamic>? query,
  }) async {
    _guard();
    final result = await client.send(
      method: method,
      url: '${AppConfig.apiV2Url}family-forms$path',
      schoolId: schoolId,
      accountId: accountId,
      data: body,
      queryParameters: query,
      privateRequest: true,
      requestTimeout: const Duration(seconds: 25),
    );
    _guard();
    if (result?['data'] is! Map) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    return Map<String, dynamic>.from(result!['data'] as Map);
  }

  @override
  Future<FamilyFormConfiguration> configuration() async {
    final result = FamilyFormConfiguration.fromJson(
      await _send('/configuration'),
    );
    if (result.schoolId != schoolId ||
        result.readerId != accountId ||
        result.children.any((child) => child.id <= 0 || child.personId <= 0)) {
      _denied();
    }
    return result;
  }

  @override
  Future<FamilyConsentPage> listing({
    int? learnerId,
    int? groupId,
    int page = 1,
  }) async {
    if ((learnerId == null) == (groupId == null) ||
        (learnerId ?? groupId!) <= 0 ||
        page < 1) {
      throw ArgumentError('Choose one current learner or group.');
    }
    final result = FamilyConsentPage.fromJson(
      await _send(
        '/consents',
        query: {
          'learner_id': ?learnerId,
          'activity_group_id': ?groupId,
          'page': page,
        },
      ),
    );
    if (groupId != null &&
            result.items.any((item) => item.activityGroupId != groupId) ||
        result.nextPage != null && result.nextPage! <= page) {
      _denied();
    }
    return result;
  }

  @override
  Future<FamilyConsentDetail> read(String id, int learnerId) async {
    _id(id);
    if (learnerId <= 0) throw ArgumentError.value(learnerId);
    final result = FamilyConsentDetail.fromJson(
      await _send('/consents/$id', query: {'learner_id': learnerId}),
    );
    if (result.id != id || result.learnerId != learnerId) _denied();
    return result;
  }

  @override
  Future<FamilyConsentManaged> managed(
    String id,
    int groupId, {
    int page = 1,
  }) async {
    _id(id);
    if (groupId <= 0 || page < 1) {
      throw ArgumentError('Choose a current group and page.');
    }
    final result = FamilyConsentManaged.fromJson(
      await _send('/consents/$id/managed', query: {'page': page}),
    );
    if (result.id != id ||
        result.activityGroupId != groupId ||
        result.nextPage != null && result.nextPage! <= page) {
      _denied();
    }
    return result;
  }

  @override
  Future<FamilyConsentSigned> sign(
    String id,
    int learnerId,
    FamilyConsentSign intent,
  ) async {
    _id(id);
    if (learnerId <= 0) throw ArgumentError.value(learnerId);
    final result = FamilyConsentSigned.fromJson(
      await _send(
        '/consents/$id/learners/$learnerId/response',
        method: 'POST',
        body: intent.toJson(),
      ),
    );
    if (result.requestId != id ||
        result.learnerId != learnerId ||
        result.decision != intent.decision ||
        result.revision != intent.revision + 1) {
      _denied();
    }
    return result;
  }

  @override
  Future<FamilyConsentCancelled> cancel(
    String id,
    String operation,
    int revision,
  ) async {
    _id(id);
    final result = FamilyConsentCancelled.fromJson(
      await _send(
        '/consents/$id/cancel',
        method: 'POST',
        body: {'operation_id': operation, 'revision': revision},
      ),
    );
    if (result.id != id ||
        result.status != 'cancelled' ||
        result.revision != revision + 1) {
      _denied();
    }
    return result;
  }

  ProfileForm _health(Map<String, dynamic> data, int personId) {
    final result = ProfileForm.fromJson(data);
    if (personId <= 0 ||
        result.personId != personId ||
        result.profileType != 'learner' ||
        result.categories.any((category) => category.key != 'health')) {
      _denied();
    }
    return ProfileForm(
      personId: result.personId,
      profileType: 'learner',
      availableProfiles: const ['learner'],
      categories: result.categories,
    );
  }

  @override
  Future<ProfileForm> health(int learnerId, int personId) async =>
      _health(await _send('/learners/$learnerId/health'), personId);
  @override
  Future<ProfileForm> saveHealth(
    int learnerId,
    int personId,
    Map<String, ProfileValue> values,
  ) async => _health(
    await _send(
      '/learners/$learnerId/health',
      method: 'PUT',
      body: {
        'values': values.map((key, value) => MapEntry(key, value.toJson())),
      },
    ),
    personId,
  );
  @override
  Future<void> uploadHealth(
    int learnerId,
    int personId,
    String field,
    String name,
    Uint8List bytes,
  ) async {
    if (!RegExp(r'^[a-z][a-z0-9_]{0,63}$').hasMatch(field) ||
        bytes.isEmpty ||
        bytes.length > 5 * 1024 * 1024) {
      throw ArgumentError('Choose a supported document up to 5 MB.');
    }
    _health(
      await _send(
        '/learners/$learnerId/health/fields/$field/file',
        method: 'POST',
        body: FormData.fromMap({
          'document': MultipartFile.fromBytes(bytes, filename: name),
        }),
      ),
      personId,
    );
  }

  @override
  Future<Uint8List> healthFile(
    int learnerId,
    int personId,
    ProfileAttachment attachment,
  ) async {
    if (!RegExp(r'^[0-9a-f-]{36}\.(pdf|png|jpg)$').hasMatch(attachment.id)) {
      throw ArgumentError.value(attachment.id);
    }
    await health(learnerId, personId);
    _guard();
    var expired = false;
    final request = client
        .getBytes(
          url:
              '${AppConfig.apiV2Url}family-forms/learners/$learnerId/health/files/${attachment.id}',
          schoolId: schoolId,
          accountId: accountId,
          requestTimeout: const Duration(seconds: 25),
        )
        .then((bytes) {
          if (expired || !isActive) {
            bytes.fillRange(0, bytes.length, 0);
            _denied();
          }
          return bytes;
        });
    final bytes = await request.timeout(
      const Duration(seconds: 25),
      onTimeout: () {
        expired = true;
        throw ApiException(
          'The private document timed out. Try again.',
          isApiMessage: true,
        );
      },
    );
    try {
      if (bytes.isEmpty || bytes.length > 5 * 1024 * 1024) {
        throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
      }
      await health(learnerId, personId);
      _guard();
      return bytes;
    } catch (_) {
      bytes.fillRange(0, bytes.length, 0);
      rethrow;
    }
  }
}

Future<bool> familyFormsDeployed(
  FamilyFormRepository repository, {
  required bool staffOnly,
}) async {
  try {
    final configuration = await repository.configuration();
    return staffOnly
        ? configuration.groups.isNotEmpty
        : configuration.children.isNotEmpty;
  } on ApiException catch (error) {
    if ([403, 404].contains(error.statusCode)) return false;
    rethrow;
  }
}
