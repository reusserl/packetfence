#!/usr/bin/perl

package pfappserver::Authentication::Store::PacketFence::User;
use base qw/Catalyst::Authentication::User Class::Accessor::Fast/;

use strict;
use warnings;

use pf::constants;
use pf::config qw($WEB_ADMIN_ALL);
use pf::authentication;
use pf::Authentication::constants qw($LOGIN_CHALLENGE);
use pf::log;
use List::MoreUtils qw(all any);
use pf::config::util;
use pf::util;

BEGIN { __PACKAGE__->mk_accessors(qw/_user _store _roles _challenge/) }

use overload '""' => sub { shift->id }, fallback => 1;

sub new {
  my ( $class, $store, $user, $roles ) = @_;

  return unless $user;
  $roles = [qw(NONE)] unless $roles;
  bless { _store => $store, _user => $user, _roles => $roles }, $class;

}

sub id {
  my $self = shift;
  return $self->_user;
}

sub supported_features {
  return {
    password => { self_check => 1, },
    session => 1,
    roles => 1,
  };
}

sub check_password {
  my ($self, $password) = @_;

  my $internal_sources = pf::authentication::getInternalAuthenticationSources();
  my ($stripped_username,$realm) = strip_username($self->_user);
  my $realm_source = get_realm_authentication_source($stripped_username, $realm);
  if($realm_source && any {$_->id eq $realm_source->id} @{$internal_sources}){
    get_logger->info("Found realm source ".$realm_source->id." for user $stripped_username in realm $realm. Using it as the only source.");
    $internal_sources = [$realm_source];
  }
  $self->{_user} = isenabled($realm_source->{'stripped_user_name'}) ? $stripped_username : $self->{_user};
  my ($result, $message, $source_id) = pf::authentication::authenticate( { 'username' => $self->_user, 'password' => $password, 'rule_class' => $Rules::ADMIN }, @{$internal_sources});

  if ($result) {
      my $value = pf::authentication::match($source_id, { username => $self->_user, 'rule_class' => $Rules::ADMIN }, $Actions::SET_ACCESS_LEVEL);
      $self->_roles([split /\s*,\s*/,$value]) if defined $value;
      if ($result == $LOGIN_CHALLENGE ) {
            $self->_challenge($message);
      }
      return (defined $value && all{ $_ ne 'NONE'} @{$self->_roles});
  }

  return $FALSE;
}

sub roles {
    my ($self) = @_;
    return @{$self->_roles};
}

*for_session = \&id;

*get_object = \&_user;

sub AUTOLOAD {
  my $self = shift;

  ( my $method ) = ( our $AUTOLOAD =~ /([^:]+)$/ );

  return if $method eq "DESTROY";

  $self->_user->$method;
}

=head1 COPYRIGHT

Copyright (C) 2005-2017 Inverse inc.

=head1 LICENSE

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301,
USA.

=cut

1;

# vim: set shiftwidth=4:
# vim: set expandtab:
# vim: set backspace=indent,eol,start:
