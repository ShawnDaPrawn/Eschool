//
// AUTO-GENERATED FILE, DO NOT MODIFY!
//

import 'package:dio/dio.dart';
import 'package:eschool_api/src/auth/api_key_auth.dart';
import 'package:eschool_api/src/auth/basic_auth.dart';
import 'package:eschool_api/src/auth/bearer_auth.dart';
import 'package:eschool_api/src/auth/oauth.dart';
import 'package:eschool_api/src/api/academics_api.dart';
import 'package:eschool_api/src/api/activities_api.dart';
import 'package:eschool_api/src/api/commerce_api.dart';
import 'package:eschool_api/src/api/communication_api.dart';
import 'package:eschool_api/src/api/consultations_api.dart';
import 'package:eschool_api/src/api/diary_api.dart';
import 'package:eschool_api/src/api/discipline_api.dart';
import 'package:eschool_api/src/api/exam_coach_api.dart';
import 'package:eschool_api/src/api/family_forms_api.dart';
import 'package:eschool_api/src/api/family_visits_api.dart';
import 'package:eschool_api/src/api/fee_statements_api.dart';
import 'package:eschool_api/src/api/foundation_classroom_api.dart';
import 'package:eschool_api/src/api/gallery_api.dart';
import 'package:eschool_api/src/api/growth_api.dart';
import 'package:eschool_api/src/api/live_updates_api.dart';
import 'package:eschool_api/src/api/my_grade_api.dart';
import 'package:eschool_api/src/api/pastoral_api.dart';
import 'package:eschool_api/src/api/pathways_api.dart';
import 'package:eschool_api/src/api/peer_tutoring_api.dart';
import 'package:eschool_api/src/api/platform_api.dart';
import 'package:eschool_api/src/api/practice_api.dart';
import 'package:eschool_api/src/api/private_staff_notices_api.dart';
import 'package:eschool_api/src/api/recognition_api.dart';
import 'package:eschool_api/src/api/school_assistance_api.dart';
import 'package:eschool_api/src/api/school_transfers_api.dart';
import 'package:eschool_api/src/api/secure_cards_api.dart';
import 'package:eschool_api/src/api/teaching_assistant_api.dart';
import 'package:eschool_api/src/api/tutor_api.dart';
import 'package:eschool_api/src/api/wallets_api.dart';
import 'package:eschool_api/src/api/weekly_digests_api.dart';
import 'package:eschool_api/src/api/wellbeing_api.dart';

class EschoolApi {
  static const String basePath = r'/api/v2';

  final Dio dio;
  EschoolApi({
    Dio? dio,
    String? basePathOverride,
    List<Interceptor>? interceptors,
  }) : this.dio =
           dio ??
           Dio(
             BaseOptions(
               baseUrl: basePathOverride ?? basePath,
               connectTimeout: const Duration(milliseconds: 5000),
               receiveTimeout: const Duration(milliseconds: 3000),
             ),
           ) {
    if (interceptors == null) {
      this.dio.interceptors.addAll([
        OAuthInterceptor(),
        BasicAuthInterceptor(),
        BearerAuthInterceptor(),
        ApiKeyAuthInterceptor(),
      ]);
    } else {
      this.dio.interceptors.addAll(interceptors);
    }
  }

  void setOAuthToken(String name, String token) {
    if (this.dio.interceptors.any((i) => i is OAuthInterceptor)) {
      (this.dio.interceptors.firstWhere((i) => i is OAuthInterceptor)
                  as OAuthInterceptor)
              .tokens[name] =
          token;
    }
  }

  /// Removes the OAuth token associated with the given [name].
  ///
  /// If no [OAuthInterceptor] is registered or no token exists for the given
  /// [name], this method has no effect.
  void removeOAuthToken(String name) {
    if (this.dio.interceptors.any((i) => i is OAuthInterceptor)) {
      (this.dio.interceptors.firstWhere((i) => i is OAuthInterceptor)
              as OAuthInterceptor)
          .tokens
          .remove(name);
    }
  }

  void setBearerAuth(String name, String token) {
    if (this.dio.interceptors.any((i) => i is BearerAuthInterceptor)) {
      (this.dio.interceptors.firstWhere((i) => i is BearerAuthInterceptor)
                  as BearerAuthInterceptor)
              .tokens[name] =
          token;
    }
  }

  /// Removes the bearer authentication token associated with the given [name].
  ///
  /// If no [BearerAuthInterceptor] is registered or no token exists for the
  /// given [name], this method has no effect.
  void removeBearerAuth(String name) {
    if (this.dio.interceptors.any((i) => i is BearerAuthInterceptor)) {
      (this.dio.interceptors.firstWhere((i) => i is BearerAuthInterceptor)
              as BearerAuthInterceptor)
          .tokens
          .remove(name);
    }
  }

  void setBasicAuth(String name, String username, String password) {
    if (this.dio.interceptors.any((i) => i is BasicAuthInterceptor)) {
      (this.dio.interceptors.firstWhere((i) => i is BasicAuthInterceptor)
              as BasicAuthInterceptor)
          .authInfo[name] = BasicAuthInfo(
        username,
        password,
      );
    }
  }

  /// Removes the basic authentication credentials associated with the given [name].
  ///
  /// If no [BasicAuthInterceptor] is registered or no credentials exist for the
  /// given [name], this method has no effect.
  void removeBasicAuth(String name) {
    if (this.dio.interceptors.any((i) => i is BasicAuthInterceptor)) {
      (this.dio.interceptors.firstWhere((i) => i is BasicAuthInterceptor)
              as BasicAuthInterceptor)
          .authInfo
          .remove(name);
    }
  }

  void setApiKey(String name, String apiKey) {
    if (this.dio.interceptors.any((i) => i is ApiKeyAuthInterceptor)) {
      (this.dio.interceptors.firstWhere(
                    (element) => element is ApiKeyAuthInterceptor,
                  )
                  as ApiKeyAuthInterceptor)
              .apiKeys[name] =
          apiKey;
    }
  }

  /// Removes the API key associated with the given [name].
  ///
  /// If no [ApiKeyAuthInterceptor] is registered or no API key exists for the
  /// given [name], this method has no effect.
  void removeApiKey(String name) {
    if (this.dio.interceptors.any((i) => i is ApiKeyAuthInterceptor)) {
      (this.dio.interceptors.firstWhere(
                (element) => element is ApiKeyAuthInterceptor,
              )
              as ApiKeyAuthInterceptor)
          .apiKeys
          .remove(name);
    }
  }

  /// Get AcademicsApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  AcademicsApi getAcademicsApi() {
    return AcademicsApi(dio);
  }

  /// Get ActivitiesApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  ActivitiesApi getActivitiesApi() {
    return ActivitiesApi(dio);
  }

  /// Get CommerceApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  CommerceApi getCommerceApi() {
    return CommerceApi(dio);
  }

  /// Get CommunicationApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  CommunicationApi getCommunicationApi() {
    return CommunicationApi(dio);
  }

  /// Get ConsultationsApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  ConsultationsApi getConsultationsApi() {
    return ConsultationsApi(dio);
  }

  /// Get DiaryApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  DiaryApi getDiaryApi() {
    return DiaryApi(dio);
  }

  /// Get DisciplineApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  DisciplineApi getDisciplineApi() {
    return DisciplineApi(dio);
  }

  /// Get ExamCoachApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  ExamCoachApi getExamCoachApi() {
    return ExamCoachApi(dio);
  }

  /// Get FamilyFormsApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  FamilyFormsApi getFamilyFormsApi() {
    return FamilyFormsApi(dio);
  }

  /// Get FamilyVisitsApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  FamilyVisitsApi getFamilyVisitsApi() {
    return FamilyVisitsApi(dio);
  }

  /// Get FeeStatementsApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  FeeStatementsApi getFeeStatementsApi() {
    return FeeStatementsApi(dio);
  }

  /// Get FoundationClassroomApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  FoundationClassroomApi getFoundationClassroomApi() {
    return FoundationClassroomApi(dio);
  }

  /// Get GalleryApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  GalleryApi getGalleryApi() {
    return GalleryApi(dio);
  }

  /// Get GrowthApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  GrowthApi getGrowthApi() {
    return GrowthApi(dio);
  }

  /// Get LiveUpdatesApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  LiveUpdatesApi getLiveUpdatesApi() {
    return LiveUpdatesApi(dio);
  }

  /// Get MyGradeApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  MyGradeApi getMyGradeApi() {
    return MyGradeApi(dio);
  }

  /// Get PastoralApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  PastoralApi getPastoralApi() {
    return PastoralApi(dio);
  }

  /// Get PathwaysApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  PathwaysApi getPathwaysApi() {
    return PathwaysApi(dio);
  }

  /// Get PeerTutoringApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  PeerTutoringApi getPeerTutoringApi() {
    return PeerTutoringApi(dio);
  }

  /// Get PlatformApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  PlatformApi getPlatformApi() {
    return PlatformApi(dio);
  }

  /// Get PracticeApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  PracticeApi getPracticeApi() {
    return PracticeApi(dio);
  }

  /// Get PrivateStaffNoticesApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  PrivateStaffNoticesApi getPrivateStaffNoticesApi() {
    return PrivateStaffNoticesApi(dio);
  }

  /// Get RecognitionApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  RecognitionApi getRecognitionApi() {
    return RecognitionApi(dio);
  }

  /// Get SchoolAssistanceApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  SchoolAssistanceApi getSchoolAssistanceApi() {
    return SchoolAssistanceApi(dio);
  }

  /// Get SchoolTransfersApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  SchoolTransfersApi getSchoolTransfersApi() {
    return SchoolTransfersApi(dio);
  }

  /// Get SecureCardsApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  SecureCardsApi getSecureCardsApi() {
    return SecureCardsApi(dio);
  }

  /// Get TeachingAssistantApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  TeachingAssistantApi getTeachingAssistantApi() {
    return TeachingAssistantApi(dio);
  }

  /// Get TutorApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  TutorApi getTutorApi() {
    return TutorApi(dio);
  }

  /// Get WalletsApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  WalletsApi getWalletsApi() {
    return WalletsApi(dio);
  }

  /// Get WeeklyDigestsApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  WeeklyDigestsApi getWeeklyDigestsApi() {
    return WeeklyDigestsApi(dio);
  }

  /// Get WellbeingApi instance, base route and serializer can be overridden by a given but be careful,
  /// by doing that all interceptors will not be executed
  WellbeingApi getWellbeingApi() {
    return WellbeingApi(dio);
  }
}
