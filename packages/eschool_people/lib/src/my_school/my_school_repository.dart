import 'package:equatable/equatable.dart';
import 'package:eschool_api/eschool_api.dart' as api;
import 'package:eschool_api/eschool_api.dart'
    show SchoolSummary, SchoolDocument, TermDates;

import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/learner_summary.dart';

export 'package:eschool_api/eschool_api.dart' show SchoolDocument, TermDates;

/// What My school shows about one school.
class MySchoolInfo extends Equatable {
  const MySchoolInfo({
    this.school,
    this.website,
    this.phone,
    this.email,
    this.address,
    this.documents = const [],
    this.termDates = const [],
  });

  /// Decode with generated DTOs, filling absent optional sections for older
  /// partial answers. The equatable view model keeps the screen API stable.
  factory MySchoolInfo.fromJson(Map<String, dynamic> json) {
    final payload = api.MySchoolInfo.fromJson({
      ...json,
      'school': readSchool(json['school'])?.toJson(),
      'website': readString(json['website']),
      'phone': readString(json['phone']),
      'email': readString(json['email']),
      'address': readString(json['address']),
      'documents': [
        if (json['documents'] is List)
          for (final item in json['documents'] as List)
            if (item is Map)
              <String, dynamic>{
                'title': '',
                'kind': null,
                'url': '',
                ...Map<String, dynamic>.from(item),
              },
      ],
      'term_dates': [
        if (json['term_dates'] is List)
          for (final item in json['term_dates'] as List)
            if (item is Map)
              <String, dynamic>{
                'name': '',
                'starts_on': null,
                'ends_on': null,
                ...Map<String, dynamic>.from(item),
              },
      ],
    });
    return MySchoolInfo(
      school: payload.school,
      website: payload.website,
      phone: payload.phone,
      email: payload.email,
      address: payload.address,
      documents: payload.documents,
      termDates: payload.termDates,
    );
  }

  final SchoolSummary? school;
  final String? website;
  final String? phone;
  final String? email;
  final String? address;
  final List<SchoolDocument> documents;
  final List<TermDates> termDates;

  @override
  List<Object?> get props => [
    school?.id,
    website,
    phone,
    email,
    address,
    documents,
    termDates,
  ];
}

abstract interface class MySchoolRepository {
  /// [schoolId]'s page; `null` when the server has no My school yet (404).
  Future<MySchoolInfo?> fetch(int schoolId);
}

class ApiMySchoolRepository implements MySchoolRepository {
  ApiMySchoolRepository(this._client, {String? baseUrl})
    : baseUrl = baseUrl ?? '${AppConfig.apiV2Url}schools';

  final ApiClient _client;
  final String baseUrl;

  @override
  Future<MySchoolInfo?> fetch(int schoolId) async {
    final Map<String, dynamic>? result = await _client.send(
      method: 'GET',
      url: '$baseUrl/$schoolId/my-school',
      schoolId: schoolId > 0 ? schoolId : null,
      notFoundAsNull: true,
    );
    return result == null ? null : MySchoolInfo.fromJson(dataMap(result));
  }
}

/// The key of My school in `EndpointAvailability`.
const String mySchoolFeature = 'my-school';

/// Whether the server has My school yet (for `AvailableOnly`).
Future<bool> mySchoolDeployed(
  MySchoolRepository repository,
  int schoolId,
) async => await repository.fetch(schoolId) != null;
