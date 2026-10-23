import 'dart:typed_data';
import 'package:dio/dio.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:equatable/equatable.dart';
import 'package:eschool_people/src/common/learner_summary.dart';

export 'package:eschool_api/eschool_api.dart'
    show ProfileForm, ProfileFormField, ProfileValue, ProfileAttachment;

class ProfileSubject extends Equatable {
  const ProfileSubject({
    this.personId,
    this.schoolId,
    this.profileType,
    this.label,
  });
  final int? personId;
  final int? schoolId;
  final String? profileType;
  final String? label;
  @override
  List<Object?> get props => [personId, schoolId, profileType];
}

abstract interface class ProfileRepository {
  Future<ProfileForm?> fetch(ProfileSubject subject, {String? profileType});
  Future<void> save(
    ProfileSubject subject,
    String profileType,
    Map<String, ProfileValue> values,
  );
}

class ProfileUpload {
  const ProfileUpload(this.name, this.bytes);
  final String name;
  final Uint8List bytes;
}

abstract interface class ProfileFileRepository {
  Future<void> upload(
    ProfileSubject subject,
    String profileType,
    String field,
    ProfileUpload file,
  );
  Future<Uint8List> readFile(ProfileSubject subject, ProfileAttachment file);
}

class ApiProfileRepository implements ProfileRepository, ProfileFileRepository {
  ApiProfileRepository(this.client);
  final ApiClient client;
  String url(ProfileSubject subject) =>
      '${AppConfig.apiV2Url}${subject.personId == null ? 'me' : 'people/${subject.personId}'}/profile';

  @override
  Future<ProfileForm?> fetch(
    ProfileSubject subject, {
    String? profileType,
  }) async {
    final result = await client.send(
      method: 'GET',
      url: url(subject),
      schoolId: subject.schoolId,
      queryParameters: {
        if ((profileType ?? subject.profileType) != null)
          'profile_type': profileType ?? subject.profileType,
      },
      notFoundAsNull: true,
    );
    return result == null ? null : ProfileForm.fromJson(dataMap(result));
  }

  @override
  Future<void> save(
    ProfileSubject subject,
    String profileType,
    Map<String, ProfileValue> values,
  ) async {
    await client.send(
      method: 'PUT',
      url: url(subject),
      schoolId: subject.schoolId,
      data: {
        'profile_type': profileType,
        'values': values.map((key, value) => MapEntry(key, value.toJson())),
      },
    );
  }

  @override
  Future<void> upload(
    ProfileSubject subject,
    String profileType,
    String field,
    ProfileUpload file,
  ) async {
    if (!RegExp(r'^[a-z][a-z0-9_]*$').hasMatch(field)) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    await client.send(
      method: 'POST',
      url: '${url(subject)}/fields/$field/file',
      schoolId: subject.schoolId,
      data: FormData.fromMap({
        'profile_type': profileType,
        'document': MultipartFile.fromBytes(file.bytes, filename: file.name),
      }),
    );
  }

  @override
  Future<Uint8List> readFile(ProfileSubject subject, ProfileAttachment file) {
    // Never attach credentials to a URL supplied by profile metadata.
    if (!RegExp(r'^[0-9a-f-]{36}\.(pdf|png|jpg)$').hasMatch(file.id)) {
      throw ApiException(ApiErrorCodes.defaultErrorMessageKey);
    }
    final person = subject.personId == null
        ? 'me'
        : 'people/${subject.personId}';
    return client.getBytes(
      url: '${AppConfig.apiBaseUrl}/protected/profile-files/$person/${file.id}',
      schoolId: subject.schoolId,
    );
  }
}

const profileFieldsFeature = 'profile-fields';
Future<bool> profilesDeployed(ProfileRepository repository) async =>
    await repository.fetch(const ProfileSubject()) != null;
