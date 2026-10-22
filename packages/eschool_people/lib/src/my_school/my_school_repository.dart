import 'package:equatable/equatable.dart';
import 'package:eschool_api/eschool_api.dart';
import 'package:eschool_core/eschool_core.dart';
import 'package:eschool_people/src/common/learner_summary.dart';

// GET /api/v2/schools/{school}/my-school (Phase 1 contract). Hand-written
// until openapi.yaml has it; then use the generated models and client.

/// A document the school publishes (code of conduct, fee schedule, ...).
class SchoolDocument extends Equatable {
  const SchoolDocument({required this.title, required this.url, this.kind});

  factory SchoolDocument.fromJson(Map<String, dynamic> json) => SchoolDocument(
    title: '${json['title'] ?? ''}',
    kind: readString(json['kind']),
    url: '${json['url'] ?? ''}',
  );

  final String title;

  /// What sort of document, as the school filed it (`code_of_conduct`,
  /// `fee_schedule`, ...); `null` when not given.
  final String? kind;
  final String url;

  @override
  List<Object?> get props => [title, kind, url];
}

/// One term of the school year.
class TermDates extends Equatable {
  const TermDates({required this.name, this.startsOn, this.endsOn});

  factory TermDates.fromJson(Map<String, dynamic> json) => TermDates(
    name: '${json['name'] ?? ''}',
    startsOn: readDate(json['starts_on']),
    endsOn: readDate(json['ends_on']),
  );

  final String name;
  final DateTime? startsOn;
  final DateTime? endsOn;

  @override
  List<Object?> get props => [name, startsOn, endsOn];
}

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

  factory MySchoolInfo.fromJson(Map<String, dynamic> json) => MySchoolInfo(
    school: readSchool(json['school']),
    website: readString(json['website']),
    phone: readString(json['phone']),
    email: readString(json['email']),
    address: readString(json['address']),
    documents: [
      if (json['documents'] is List)
        for (final Object? item in json['documents'] as List)
          if (item is Map)
            SchoolDocument.fromJson(Map<String, dynamic>.from(item)),
    ],
    termDates: [
      if (json['term_dates'] is List)
        for (final Object? item in json['term_dates'] as List)
          if (item is Map) TermDates.fromJson(Map<String, dynamic>.from(item)),
    ],
  );

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
      notFoundAsNull: true,
    );
    return result == null ? null : MySchoolInfo.fromJson(dataMap(result));
  }
}

/// Whether the server has My school yet (for `AvailableOnly`).
Future<bool> mySchoolDeployed(
  MySchoolRepository repository,
  int schoolId,
) async => await repository.fetch(schoolId) != null;
