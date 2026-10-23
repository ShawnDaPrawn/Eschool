import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:equatable/equatable.dart';
import 'package:eschool_people/src/common/learner_summary.dart';

export 'package:eschool_api/eschool_api.dart'
    show ProfileForm, ProfileFormField, ProfileValue;

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

class ApiProfileRepository implements ProfileRepository {
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
}

const profileFieldsFeature = 'profile-fields';
Future<bool> profilesDeployed(ProfileRepository repository) async =>
    await repository.fetch(const ProfileSubject()) != null;
